{{-- Success message --}}
@if(session('success'))
    <x-alert type="success" dismissible>
        {{ session('success') }}
    </x-alert>
@endif

{{-- Error message --}}
@if(session('error'))
    <x-alert type="error" dismissible>
        {{ session('error') }}
    </x-alert>
@endif

{{-- Warning message --}}
@if(session('warning'))
    <x-alert type="warning" dismissible>
        {{ session('warning') }}
    </x-alert>
@endif

{{-- Info message --}}
@if(session('info'))
    <x-alert type="info" dismissible>
        {{ session('info') }}
    </x-alert>
@endif

{{-- Validation errors --}}
@if($errors->any())
    <x-alert type="error">
        <ul class="list-disc pl-5 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
