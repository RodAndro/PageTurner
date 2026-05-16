@props(['book'])

<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
<x-shared-book-card :book="$book"/>
        

        
        <div class="flex justify-between items-center mb-3">
            <span class="text-2xl font-bold text-primary-600">₱{{ number_format($book->price, 2) }}</span>
            
            <div class="text-sm">
                @if($book->stock_quantity > 0)
                    <span class="text-black-700">
                        <span class="font-semibold">Stock left:</span> 
                        <span class="text-primary-600">
                            {{ $book->stock_quantity }}
                        </span>
                    </span>
                @else
                    <span class="text-red-600 font-semibold">Out of Stock</span>
                @endif
            </div>
        </div>
        
        <!-- Add to Cart Button - Not wrapped in link -->
        <x-add-to-cart-button :book="$book"/>

    </div>
    
</div>