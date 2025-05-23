<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\ShoppingCart;
use App\Models\ShoppingCartItem;
use App\Models\ProductVariant;
use App\Models\Coupon;
use App\Models\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShoppingCartController extends Controller
{
    public function index()
    {
        $cart = $this->getOrCreateCart();
        $addresses = auth()->user()->addresses;
        $payment_methods = PaymentMethod::all();

        return view('shopping-cart.index', compact('cart', 'addresses', 'payment_methods'));
    }

    public function addToCart(Request $request)
    {
        $product_id = $request->product_id;
        $color_id = $request->color_id;
        $size_id = $request->size_id;
        $material_id = $request->material_id;
        $quantity = 1;

        $product_variant = ProductVariant::where('product_id', $product_id)
            ->where('color_id', $color_id)
            ->where('size_id', $size_id)
            ->where('material_id', $material_id)
            ->first();

        if (!$product_variant) {
            return redirect()->back()->with('error', 'Product variant not found.');
        }

        $cart = ShoppingCart::firstOrCreate(['user_id' => Auth::id()]);

        $cartItem = ShoppingCartItem::where('shopping_cart_id', $cart->id)
            ->where('product_variant_id', $product_variant->id)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            ShoppingCartItem::create([
                'shopping_cart_id' => $cart->id,
                'product_variant_id' => $product_variant->id,
                'quantity' => $quantity,
                'price' => $product_variant->price,
            ]);
        }

        ActionLog::create([
            'user_id' => Auth::id(),
            'action' => 'create',
            'target' => 'shopping_cart_item',
            'status' => 'success',
            'ip_address' => request()->ip(),
            'details' => 'Product added to cart, Product ID: ' . $product_variant->product_id,
        ]);

        return back()->with('success', 'Product added to cart successfully.');
    }

    public function removeFromCart(ShoppingCartItem $item)
    {
        $item->delete();

        ActionLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete',
            'target' => 'shopping_cart_item',
            'status' => 'success',
            'ip_address' => request()->ip(),
            'details' => 'Product removed from cart, Product Variant ID: ' . $item->product_variant_id,
        ]);

        return back()->with('success', 'Product removed from cart successfully.');
    }

    public function updateQuantities(Request $request)
    {
        $request->validate([
            'action' => 'required|in:increase,decrease',
            'item_id' => 'required|exists:shopping_cart_items,id',
        ]);

        $item = ShoppingCartItem::find($request->item_id);

        if ($item && $item->shopping_cart_id === $this->getOrCreateCart()->id) {
            if ($request->action === 'increase') {
                $item->quantity++;
            } elseif ($request->action === 'decrease' && $item->quantity > 1) {
                $item->quantity--;
            }
            $item->save();

            ActionLog::create([
                'user_id' => Auth::id(),
                'action' => 'update',
                'target' => 'shopping_cart_item',
                'status' => 'success',
                'ip_address' => request()->ip(),
                'details' => 'Cart quantity updated, Item ID: ' . $item->id,
            ]);
        }

        return back()->with('success', 'Cart quantity updated successfully.');
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return back()->with('error', 'Invalid coupon code.');
        }

        if ($coupon->usage_limit != null) {
            if ($coupon->used_count >= $coupon->usage_limit) {
                return back()->with('error', 'This coupon has reached its maximum usage limit.');
            } elseif ($coupon->is_active == 0 || $coupon->is_active == false) {
                return back()->with('error', 'This coupon is not active anymore.');
            }
        }

        $cart = $this->getOrCreateCart();

        $cart->coupon_id = $coupon->id;
        $cart->save();

        ActionLog::create([
            'user_id' => Auth::id(),
            'action' => 'update',
            'target' => 'shopping_cart',
            'status' => 'success',
            'ip_address' => request()->ip(),
            'details' => 'Coupon applied, Coupon Code: ' . $coupon->code,
        ]);

        return back()->with('success', 'Coupon applied successfully.');
    }

    public function removeCoupon()
    {
        $cart = $this->getOrCreateCart();
        $cart->coupon_id = null;
        $cart->save();

        ActionLog::create([
            'user_id' => Auth::id(),
            'action' => 'update',
            'target' => 'shopping_cart',
            'status' => 'success',
            'ip_address' => request()->ip(),
            'details' => 'Coupon removed',
        ]);

        return back()->with('success', 'Coupon removed successfully.');
    }

    private function getOrCreateCart()
    {
        if (Auth::check()) {
            return ShoppingCart::firstOrCreate(['user_id' => Auth::id()]);
        }
    }
}
