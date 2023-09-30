<x-app-layout :title="__('Create file')">
    <x-breadcrumbs :directories="$directory?->breadcrumbs" class="px-4"/>

    <div class="card bg-base-200 shadow-lg max-sm:rounded-none md:w-2/3 md:mx-auto">
        <div class="card-body">
            <h2 class="card-title">
                <i class="fa-solid fa-file-circle-plus mr-2"></i>
                {{ __('Create file') }}
            </h2>

            <form action="{{ route('files.store') }}" method="post" enctype="multipart/form-data">
                @csrf

                @if ($directory)
                    <input type="hidden" name="directory_uuid" value="{{ $directory->uuid }}">
                @endif

                <x-form-field name="file" class="md:w-2/3">
                    <x-slot:label>{{ __('File') }}</x-slot:label>

                    <x-input name="file" type="file" inputClass="file-input" required onchange="onSelectedFileChange(this.files[0])" />
                </x-form-field>

                <x-form-field name="name"  class="md:w-2/3">
                    <x-slot:label>{{ __('Name') }}</x-slot:label>

                    <div class="relative" id="name-input-container">
                        <x-input name="name" :value="old('name')" required />
                    </div>

                    <x-slot:hint>{{ __('Info') }}: {{ __('Without file extension') }}</x-slot:hint>
                </x-form-field>

                <div class="form-control w-fit">
                    <label class="label cursor-pointer gap-4">
                        <span class="label-text">{{ __('Encrypt file on the server') }}</span> 
                        <input type="checkbox" @checked(old('encrypt')) class="checkbox checkbox-secondary" name="encrypt" />
                    </label>
                </div>

                <x-form-field name="description" class="md:w-2/3">
                    <x-slot:label optional>{{ __('Description') }}</x-slot:label>

                    <textarea
                        id="description"
                        name="description"
                        class="textarea leading-snug h-24{{ $errors->get('description') ? ' input-error' : '' }}"
                    >{{ old('description') }}</textarea>
                </x-form-field>

                <div class="card-actions justify-end">
                    <a href="{{ url()->previous() }}" class="btn btn-neutral">{{ __('Cancel') }}</a>
                    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function onSelectedFileChange(file) {
                var name = file.name;
                var extension = '';
    
                var lastDot = name.lastIndexOf('.');
    
                if(lastDot !== -1) {
                    extension = name.substring(lastDot + 1);
                    name = name.substring(0, lastDot);
                }
    
                document.getElementById('name').value = name;
                
                var nameInputExtension = document.getElementById('name-input-extension');
    
                if(nameInputExtension) {
                    nameInputExtension.remove();
                }
    
                if(extension) {
                    document
                        .getElementById('name-input-container')
                        .insertAdjacentHTML(
                            'beforeend',
                            '<span id="name-input-extension" class="absolute top-0 bottom-0 right-0 px-4 flex items-center bg-base-200/50 text-base-content/70 rounded-lg">' +
                                '.' +
                                extension +
                                '</span>'
                        );
                }
            }
            
            /*
            |------------
            |    INIT
            |------------
            */
            if(document.getElementById('file').files.length === 1) {
                onSelectedFileChange(document.getElementById('file').files[0]);
            }
        </script>
    @endpush
</x-app-layout>