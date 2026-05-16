<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-bold mb-2">Export Users</h2>
                    <p class="text-gray-600 mb-6">Export user data with optional GDPR PII redaction for compliance</p>

                    <form action="{{ route('admin.import-export.users-export-store') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="format" class="block text-sm font-medium text-gray-700">Export Format</label>
                                <select name="format" id="format" required
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="xlsx">Excel (XLSX)</option>
                                    <option value="csv">CSV</option>
                                </select>
                                @error('format')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="redact_pii" class="block text-sm font-medium text-gray-700">GDPR PII Redaction</label>
                                <div class="mt-2 flex items-center">
                                    <input type="checkbox" name="redact_pii" id="redact_pii" value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <label for="redact_pii" class="ml-2 text-sm text-gray-600">
                                        Enable PII redaction for GDPR compliance
                                    </label>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">When enabled, email addresses and other sensitive data will be masked</p>
                            </div>
                        </div>

                        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <h3 class="text-sm font-bold text-blue-900 mb-2">PII Redaction Details</h3>
                            <ul class="text-xs text-blue-700 space-y-1">
                                <li><strong>When Enabled:</strong> Email addresses will be masked (e.g., user***@domain.com)</li>
                                <li><strong>When Disabled:</strong> Full data export including email addresses</li>
                                <li>Always excludes: Password hashes and sensitive tokens</li>
                                <li>Recommended for external data sharing</li>
                            </ul>
                        </div>

                        <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h3 class="text-sm font-bold text-gray-900 mb-3">User Data Columns to Export</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="id" checked disabled class="rounded text-gray-400">
                                    <span class="ml-2 text-sm text-gray-700">User ID <span class="text-gray-500">(always included)</span></span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="name" checked class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Full Name</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="email" checked class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Email Address</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="role" checked class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">User Role</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="email_verified" class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Email Verified</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="email_verified_at" class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Email Verified Date</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="created_at" checked class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Account Created</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="columns[]" value="updated_at" class="rounded">
                                    <span class="ml-2 text-sm text-gray-700">Last Updated</span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <h3 class="text-sm font-bold text-yellow-900 mb-2">Export Information</h3>
                            <ul class="text-xs text-yellow-700 space-y-1">
                                <li>Large exports (>10,000 records) will be queued and processed in the background</li>
                                <li>You will receive an email notification when the export is ready</li>
                                <li>Exported files are stored securely and can be downloaded from the Status page</li>
                                <li>All export operations are logged for audit purposes</li>
                            </ul>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <button type="submit" class="inline-flex items-center justify-center bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Download Users Export
                            </button>
                            <a href="{{ route('admin.import-export.status') }}" class="inline-flex items-center justify-center bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                View Export Status
                            </a>
                            <a href="{{ route('admin_home') }}" class="inline-flex items-center justify-center bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                        </div>
                    </form>

                    @if ($errors->any())
                        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <h3 class="text-sm font-bold text-red-900 mb-2">Errors</h3>
                            <ul class="text-xs text-red-700 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
