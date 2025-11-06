<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('Nome do Formulário (submission-name)')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $form->name ?? '')" required autofocus autocomplete="off" />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Este nome é crucial. Ele será usado para mapear o formulário a uma coleção específica no arquivo item-submission.xml. Ex: 'artigo' ou 'tcc'.
        </p>
    </div>
</div>
