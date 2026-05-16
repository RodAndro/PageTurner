<div id="modal-container"
     class="fixed inset-0 flex items-center justify-center opacity-0 pointer-events-none bg-black bg-opacity-50 z-50">

    <div class="bg-white p-6 rounded-lg shadow-xl w-[800px] max-w-full max-h-[90vh] overflow-y-auto">
        
        <div class="flex gap-6">
            <div class="flex-shrink-0 w-48">
                <img id="modal-book-image"
                     src="" 
                     alt="" 
                     class="w-full h-64 object-cover rounded-lg border">
            </div>
            
            <div class="flex-1">
                <h1 id="modal-book-title" class="text-2xl font-bold mb-2">
                    Book Title
                </h1>
                
                <p id="modal-book-author" class="text-lg text-gray-600 mb-3">
                    By: Author Name
                </p>
                
                <p id="modal-book-description" class="text-gray-700 mb-4 line-clamp-3">
                    Book description
                </p>
                
                <div class="mb-4">
                    <span class="text-lg font-semibold text-green-600">$</span>
                    <span id="book-price" class="text-lg font-semibold text-green-600" data-price="0">0.00</span>
                </div>
                
                <div class="mb-4">
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity:</label>
                    <div class="flex items-center gap-3">
                        <button id="decrease-qty" 
                                class="bg-gray-200 hover:bg-gray-300 text-gray-700 w-8 h-8 rounded-full flex items-center justify-center">
                            -
                        </button>
                        <input type="number" 
                               id="quantity" 
                               value="1" 
                               min="1" 
                               max="10" 
                               class="w-16 text-center border border-gray-300 rounded px-2 py-1">
                        <button id="increase-qty" 
                                class="bg-gray-200 hover:bg-gray-300 text-gray-700 w-8 h-8 rounded-full flex items-center justify-center">
                            +
                        </button>
                    </div>
                    <p id="modal-stock-info" class="text-sm text-gray-500 mt-1">Stock available: 0</p>
                </div>
                
                <div class="mb-6">
                    <div class="text-lg">
                        <span class="font-medium">Total: $</span>
                        <span id="total-price" class="font-bold text-xl text-green-600">0.00</span>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <form id="add-to-cart-form" action="{{ route('add-to-cart') }}" method="POST" class="flex-1">
                        @csrf
                        <input type="hidden" name="book_id" id="form-book-id" value="">
                        <input type="hidden" name="quantity" id="form-quantity" value="1">
                        <input type="submit" 
                               class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition cursor-pointer"
                               value="Add to Cart">
                    </form>
                    <button id="close"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>



<script>
const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
const modalContainer = document.getElementById('modal-container');
const closeButton = document.getElementById('close');
const quantityInput = document.getElementById('quantity');
const formQuantityInput = document.getElementById('form-quantity');
const formBookIdInput = document.getElementById('form-book-id');
const decreaseBtn = document.getElementById('decrease-qty');
const increaseBtn = document.getElementById('increase-qty');
const totalPriceElement = document.getElementById('total-price');
const bookPriceElement = document.getElementById('book-price');

// Modal content elements
const modalBookImage = document.getElementById('modal-book-image');
const modalBookTitle = document.getElementById('modal-book-title');
const modalBookAuthor = document.getElementById('modal-book-author');
const modalBookDescription = document.getElementById('modal-book-description');
const modalStockInfo = document.getElementById('modal-stock-info');

let bookPrice = 0;
let maxStock = 10;

function populateModal(button) {
    // Extract data from the clicked button
    const bookId = button.dataset.bookId;
    const title = button.dataset.bookTitle;
    const author = button.dataset.bookAuthor;
    const description = button.dataset.bookDescription;
    const price = parseFloat(button.dataset.bookPrice);
    const image = button.dataset.bookImage;
    const stock = parseInt(button.dataset.bookStock);
    
    // Update modal content
    modalBookImage.src = image;
    modalBookImage.alt = title;
    modalBookTitle.textContent = title;
    modalBookAuthor.textContent = `By: ${author}`;
    modalBookDescription.textContent = description;
    bookPriceElement.textContent = price.toFixed(2);
    bookPriceElement.dataset.price = price;
    modalStockInfo.textContent = `Stock available: ${stock}`;
    
    // Update form inputs
    formBookIdInput.value = bookId;
    quantityInput.max = stock;
    quantityInput.value = 1;
    formQuantityInput.value = 1;
    
    // Store current values
    bookPrice = price;
    maxStock = stock;
    
    updateTotalPrice();
}

function showModal(button) {
    populateModal(button);
    modalContainer.classList.remove('opacity-0', 'pointer-events-none');
    modalContainer.classList.add('opacity-100');
}

function hideModal() {
    modalContainer.classList.add('opacity-0', 'pointer-events-none');
    modalContainer.classList.remove('opacity-100');
    
    quantityInput.value = 1;
    formQuantityInput.value = 1;
    updateTotalPrice();
}

function updateTotalPrice() {
    const quantity = parseInt(quantityInput.value) || 1;
    const total = (bookPrice * quantity).toFixed(2);
    totalPriceElement.textContent = total;
    
    if (formQuantityInput) {
        formQuantityInput.value = quantity;
    }
}

function changeQuantity(change) {
    const currentQuantity = parseInt(quantityInput.value) || 1;
    const newQuantity = currentQuantity + change;
    
    if (newQuantity >= 1 && newQuantity <= maxStock) {
        quantityInput.value = newQuantity;
        updateTotalPrice();
    }
}

// Event listeners
addToCartButtons.forEach(button => {
    button.addEventListener('click', function() {
        if (!this.disabled) {
            showModal(this);
        }
    });
});

closeButton.addEventListener('click', hideModal);

decreaseBtn.addEventListener('click', () => changeQuantity(-1));
increaseBtn.addEventListener('click', () => changeQuantity(1));

quantityInput.addEventListener('input', function() {
    if (parseInt(this.value) > maxStock) {
        this.value = maxStock;
    } else if (parseInt(this.value) < 1 || isNaN(parseInt(this.value))) {
        this.value = 1;
    }
    updateTotalPrice();
});

modalContainer.addEventListener('click', function(e) {
    if (e.target === modalContainer) {
        hideModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideModal();
    }
});
</script>