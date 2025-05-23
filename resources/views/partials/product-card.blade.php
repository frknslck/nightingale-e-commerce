<div class="card h-100 position-relative">
    @auth
        <form action="{{ route('wishlist.toggle') }}" method="POST" class="wishlist-form position-absolute top-0 start-0 m-2">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <button type="submit" 
                class="btn btn-sm {{ Auth::user()->wishlist->contains('product_id', $product->id) ? 'btn-danger' : 'btn-outline-danger' }}">
                <i class="fas fa-heart"></i>
            </button>
        </form>
    @endauth

    @if($product->best_seller)
        <span class="badge bg-danger position-absolute top-0 end-0 m-2">Best Seller</span>
    @endif

    <img src="{{ $product->image_url ?? 'https://via.placeholder.com/300x400' }}" class="card-img-top" alt="{{ $product->name }}">
    <div class="card-body d-flex flex-column">
        <h5 class="card-title">{{ $product->name }}</h5>
        <p class="text-muted flex-grow-1">{{ Str::limit($product->description, 80) }}</p>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex flex-column">
                @if($product->discounted_price < $product->price)
                    <div class="d-flex align-items-baseline">
                        <span class="fw-bold text-danger me-2">
                            ${{ number_format($product->discounted_price, 2) }}
                        </span>
                        <small class="text-muted text-decoration-line-through">
                            ${{ number_format($product->price, 2) }}
                        </small>
                    </div>
                @else
                    <span class="fw-bold">
                        ${{ number_format($product->price, 2) }}
                    </span>
                @endif
            </div>
            <span class="badge bg-secondary">
                {{ $product->variants->count() > 0 ? $product->variants->count().' Variant(s)' : 'No Variants' }}
            </span>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center">
                <span class="me-1">{{ number_format($product->average_rating, 1) }}</span>
                @for ($i = 1; $i <= 5; $i++)
                    <i class="fas fa-star{{ $i <= round($product->average_rating) ? ' text-warning' : ' text-muted' }}"></i>
                @endfor
            </div>
            <span class="text-muted">({{ $product->rating_count }})</span>
        </div>
        <a href="{{ route('products.show', $product) }}" class="btn btn-primary w-100">View Details</a>
    </div>
</div>

<!-- This project developed by Furkan Bülbül https://github.com/frknslck -->