<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Item: {{ $item->asset_id }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto mt-6 bg-white p-6 rounded-lg border">
        @if($errors->any())
            <div class="p-3 mb-4 bg-red-50 border border-red-200 rounded">
                <ul class="list-disc list-inside text-sm text-red-700">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('items.update', $item->asset_id) }}">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Asset ID</label>
                    <input name="asset_id" value="{{ old('asset_id',$item->asset_id) }}" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">UID</label>
                    <input name="uid" value="{{ old('uid',$item->uid) }}" class="w-full border rounded p-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Name</label>
                    <input name="name" value="{{ old('name',$item->name) }}" class="w-full border rounded p-2" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Detail</label>
                    <input name="detail" value="{{ old('detail',$item->detail) }}" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Accessories</label>
                    <input name="accessories" value="{{ old('accessories',$item->accessories) }}" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Type ID</label>
                    <input name="type_id" value="{{ old('type_id',$item->type_id) }}" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Serial No</label>
                    <input name="serial_no" value="{{ old('serial_no',$item->serial_no) }}" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Purchase Date</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date',$item->purchase_date) }}" class="w-full border rounded p-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Remarks</label>
                    <input name="remarks" value="{{ old('remarks',$item->remarks) }}" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Status</label>
                    <select name="status" class="w-full border rounded p-2" required>
                        @foreach(['available','borrowed','retire','under repair','stolen','missing/lost'] as $st)
                            <option value="{{ $st }}" @selected(old('status',$item->status)===$st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <a href="{{ route('nfc.inventory') }}" class="px-4 py-2 rounded border">Cancel</a>
                <button class="px-4 py-2 rounded text-white" style="background:#2563eb;">Save changes</button>
            </div>
        </form>
    </div>
</x-app-layout>
