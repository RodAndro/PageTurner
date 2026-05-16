@php
    $recommendations = $recommendations ?? [];
    $loading = $loading ?? false;
@endphp

<div class="bg-white rounded-lg shadow-md p-6 mb-8" x-data="aiRecommendations()">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-semibold text-gray-900">
            <svg class="inline-block w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            AI Book Recommendations
        </h3>
        <button 
            @click="toggleForm()" 
            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
        >
            <span x-show="!showForm">Get Recommendations</span>
            <span x-show="showForm">Hide</span>
        </button>
    </div>

    <div x-show="showForm" x-transition class="mb-4">
        <form @submit.prevent="getRecommendations()">
            <div class="mb-3">
                <label for="ai-query" class="block text-sm font-medium text-gray-700 mb-2">
                    What kind of books are you looking for?
                </label>
                <textarea 
                    id="ai-query"
                    x-model="query"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="e.g., I want fantasy books with strong female protagonists, or Recommend some programming books for beginners..."
                    required
                ></textarea>
            </div>
            
            <div class="mb-3">
                <label for="category-filter" class="block text-sm font-medium text-gray-700 mb-2">
                    Category (optional)
                </label>
                <select 
                    id="category-filter"
                    x-model="categoryId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">All Categories</option>
                    @foreach(\App\Models\Category::all() as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <button 
                type="submit"
                :disabled="loading"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors duration-200"
            >
                <span x-show="!loading">Get AI Recommendations</span>
                <span x-show="loading">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Getting recommendations...
                </span>
            </button>
        </form>
    </div>

    <div x-show="hasRecommendations" x-transition class="space-y-4">
        <div x-html="message" class="text-gray-700 mb-4"></div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="recommendation in recommendations" :key="recommendation.title">
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                    <h4 class="font-semibold text-gray-900 mb-2" x-text="recommendation.title"></h4>
                    <p class="text-sm text-gray-600 mb-2" x-text="recommendation.reason"></p>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span x-text="recommendation.category"></span>
                        <span x-text="recommendation.price_range"></span>
                    </div>
                    <p class="text-sm text-gray-700 mt-2" x-text="recommendation.description"></p>
                </div>
            </template>
        </div>

        <div x-show="isFallback" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <p class="text-sm text-yellow-800">
                <strong>Note:</strong> AI service is temporarily unavailable. Showing general recommendations above.
            </p>
        </div>
    </div>

    <div x-show="error" x-transition class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
        <p class="text-sm text-red-800" x-text="error"></p>
    </div>
</div>

<script>
function aiRecommendations() {
    return {
        showForm: false,
        query: '',
        categoryId: '',
        loading: false,
        recommendations: [],
        message: '',
        error: '',
        isFallback: false,

        get hasRecommendations() {
            return this.recommendations.length > 0;
        },

        toggleForm() {
            this.showForm = !this.showForm;
            if (!this.showForm) {
                this.clearResults();
            }
        },

        async getRecommendations() {
            if (!this.query.trim()) return;

            this.loading = true;
            this.error = '';
            this.clearResults();

            try {
                const params = new URLSearchParams({
                    query: this.query
                });

                if (this.categoryId) {
                    params.append('category_id', this.categoryId);
                }

                const response = await fetch(`/api/v1/ai/recommendations?${params}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    this.recommendations = data.recommendations || [];
                    this.message = data.message || '';
                    this.isFallback = data.fallback || false;
                } else {
                    this.error = data.error || 'Failed to get recommendations';
                }
            } catch (error) {
                this.error = 'Network error. Please try again.';
                console.error('AI Recommendations Error:', error);
            } finally {
                this.loading = false;
            }
        },

        clearResults() {
            this.recommendations = [];
            this.message = '';
            this.error = '';
            this.isFallback = false;
        }
    }
}
</script>
