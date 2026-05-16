<!-- Edit Order Status Modal -->
<div id="editOrderModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Update Order Status</h3>
            <form id="editOrderForm" method="POST">
                @csrf
                @method('PATCH')
                
                <div class="mb-4">
                    <label for="edit_status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <select id="edit_status" name="status" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Shipped">Shipped</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Failed">Failed</option>
                        <option value="Cart">Cart</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeEditOrderModal()" 
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openEditOrderModal(orderId, status) {
        // Update form action
        const form = document.getElementById('editOrderForm');
        form.action = `/admin/orders/${orderId}`;
        
        // Set current status
        document.getElementById('edit_status').value = status;
        
        // Show modal
        document.getElementById('editOrderModal').classList.remove('hidden');
    }

    function closeEditOrderModal() {
        document.getElementById('editOrderModal').classList.add('hidden');
    }
</script>
