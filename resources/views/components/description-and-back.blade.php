
@props(['book'])

<div class="mt-8">
        <h3 class="text-xl font-semibold text-gray-800 mb-3">Description</h3>
        <p class="text-gray-700 leading-relaxed">{{ $book->description }}</p>
    </div>

    <div class="mt-6">
        <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 font-medium">
            ← Back to Books
        </a>
</div>