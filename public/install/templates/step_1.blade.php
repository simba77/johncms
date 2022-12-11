@extends('install::layout')
@section('content')
    <h4 class="mb-3"><?= __('Welcome to JohnCMS installation wizard') ?></h4>

    <p>
        <?= __('During the installation process, a basic check of the hosting will be performed for compliance with the CMS requirements.') ?> <br>
        <?= __('If errors occurred during the installation stage and you do not know how to solve them, you can ask for help on <a href="https://johncms.com/forum/" class="text-underline" rel="nofollow" target="_blank">our forum.</a>') ?>

    </p>

    <h4 class="mb-3"><?= __('Choose language') ?></h4>

    <form action="/install/?step=2" method="post" class="mb-3">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3">
            <?php
            foreach ($data['lng_list'] as $key => $val): ?>
            <div class="col">
                <div class="custom-control custom-radio mb-2">
                    <input type="radio" id="lang_<?= $key ?>" name="lang" class="form-check-input" value="<?= $key ?>" <?= ($key == $locale ? ' checked="checked"' : '') ?>>
                    <label class="form-check-label" for="lang_<?= $key ?>">
                        <img src="<?= asset('images/flags/' . strtolower($key) . '.svg') ?>" class="icon icon-flag" alt=".">
                            <?= $val['name'] ?>
                    </label>
                </div>
            </div>
            <?php
            endforeach ?>
        </div>
        <div class="mt-3">
            <button type="submit" name="submit" value="submit" class="btn btn-primary"><?= __('Continue') ?></button>
        </div>
    </form>

    @push('scripts')
        <script>
            window.addEventListener("load", (event) => {
                $(document).on('change', 'input[name=lang]', function () {
                    document.location.href = '/install/?set_locale=' + $(this).val();
                })
            });
        </script>
    @endpush
@endsection
