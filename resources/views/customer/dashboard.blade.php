<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Dashboard') }}
            </h2>
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ __('Profile') }}
            </a>
        </div>
    </x-slot>
    
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            <x-flash-messages/>

            <!-- Welcome Message -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-gray-900">Welcome back, {{ $user->first_name }}!</h3>
                    <p class="text-gray-600 mt-2">Here's an overview of your account and recent activity.</p>
                </div>
            </div>

            <!-- Order Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h4 class="text-sm font-medium text-gray-500">Total Orders</h4>
                        <p class="text-2xl font-bold text-gray-900 mt-2">{{ $totalOrders }}</p>
                    </div>
                </div>

                @php
                    $statuses = [
                        'Pending' => 'bg-yellow-100 text-yellow-800',
                        'Processing' => 'bg-primary-100 text-primary-800',
                        'Delivered' => 'bg-green-100 text-green-800'
                    ];
                @endphp

                @foreach(['Pending', 'Processing', 'Delivered'] as $status)
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h4 class="text-sm font-medium text-gray-500">{{ $status }}</h4>
                            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $orderStatusCounts[$status] ?? 0 }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Recently Purchased Books -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Recently Purchased Books</h3>
                    <a href="{{ route('purchased-books.show') }}" class="text-sm text-indigo-600 hover:text-indigo-900">View All →</a>
                </div>

                @if($purchasedBooks->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        @foreach($purchasedBooks as $book)
                            <x-purchased-book-card :book="$book" />
                        @endforeach
                    </div>
                @else
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <p class="text-gray-500 mb-4">No purchased books yet. Start exploring!</p>
                            <a href="{{ route('dashboard') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Browse Books
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Recent Orders -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Orders</h3>
                    <a href="{{ route('orders.show') }}" class="text-sm text-indigo-600 hover:text-indigo-900">View All →</a>
                </div>

                @if($recentOrders->count() > 0)
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <table class="min-w-full">
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($recentOrders as $order)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#{{ $order->id }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $order->created_at->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900">₱{{ number_format($order->total_amount, 2) }}</td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    @if($order->status == 'Pending') bg-yellow-100 text-yellow-800
                                                    @elseif($order->status == 'Processing') bg-blue-100 text-blue-800
                                                    @elseif($order->status == 'Shipped') bg-purple-100 text-purple-800
                                                    @elseif($order->status == 'Delivered') bg-green-100 text-green-800
                                                    @elseif($order->status == 'Cancelled') bg-red-100 text-red-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                    {{ $order->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-gray-500">You haven't placed any orders yet.</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Data Portability Section -->
            <div class="mb-8">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <svg class="w-5 h-5 inline mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                            </svg>
                            Data Portability
                        </h3>
                        <p class="text-gray-600 mb-6">Download your personal data, order history, and reading history in various formats.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Export Personal Data -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center mb-3">
                                    <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <div>
                                        <h4 class="font-medium text-gray-900">Personal Data</h4>
                                        <p class="text-sm text-gray-600">GDPR compliant export</p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mb-3">Download all your account information, orders, and reviews in JSON format.</p>
                                <form action="{{ route('dashboard.export.personal') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="w-full bg-blue-600 text-white px-3 py-2 rounded-md text-sm hover:bg-blue-700 transition-colors">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Export JSON
                                    </button>
                                </form>
                            </div>

                            <!-- Export Order History -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center mb-3">
                                    <svg class="w-8 h-8 text-green-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <div>
                                        <h4 class="font-medium text-gray-900">Order History</h4>
                                        <p class="text-sm text-gray-600">Complete order records</p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mb-3">Download your complete order history with details and items.</p>
                                <div class="space-y-2">
                                    <form action="{{ route('dashboard.export.orders') }}?format=excel" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="w-full bg-green-600 text-white px-3 py-2 rounded-md text-sm hover:bg-green-700 transition-colors">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Export Excel
                                        </button>
                                    </form>
                                    <form action="{{ route('dashboard.export.orders') }}?format=pdf" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="w-full bg-gray-600 text-white px-3 py-2 rounded-md text-sm hover:bg-gray-700 transition-colors">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Export PDF
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Export Reading History -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center mb-3">
                                    <svg class="w-8 h-8 text-purple-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <div>
                                        <h4 class="font-medium text-gray-900">Reading History</h4>
                                        <p class="text-sm text-gray-600">Books & reviews</p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mb-3">Download your purchased books, reviews, and reading statistics.</p>
                                <form action="{{ route('dashboard.export.reading') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="w-full bg-purple-600 text-white px-3 py-2 rounded-md text-sm hover:bg-purple-700 transition-colors">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Export JSON
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-800">
                                <strong>Data Privacy:</strong> All exports are generated on-demand and contain only your personal data. 
                                Exports are logged for security purposes. For any data-related questions, please contact our support team.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Reviews -->
            <div>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">My Reviews ({{ $reviewCount }})</h3>
                    <a href="{{ route('purchased-books.show') }}" class="text-sm text-indigo-600 hover:text-indigo-900">Write a Review →</a>
                </div>

                @if($recentReviews->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentReviews as $review)
                            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $review->book->title }}</p>
                                        <p class="text-sm text-gray-600">{{ $review->created_at->format('M d, Y') }}</p>
                                    </div>
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                        <span class="ml-2 text-sm font-medium text-gray-700">{{ $review->rating }}/5</span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-700">{{ Str::limit($review->comment, 200) }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            <p class="text-gray-500">You haven't written any reviews yet.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
