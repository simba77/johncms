<?php

$fields = $data['fields'] ?? [];
$errors = $data['errors'] ?? [];

?>

@extends('install::layout')
@section('content')

    <h4 class="mb-2 mt-3 fw-bold"><?= __('Database connection settings') ?></h4>

    <?php
    if (! empty($errors['unknown'])): ?>
    <div class="alert alert-danger"><?= implode('<br>', $errors['unknown']) ?></div>
    <?php
    endif ?>
    <form action="/install/?step=3" method="post" class="mb-3">
<?php
        if ($data['db_version_error']): ?><?php
        endif; ?>
        <input type="hidden" name="anyway_continue" value="yes">

        <div class="form-group">
            <label for="db_host"><?= __('DB Host') ?></label>
            <input type="text"
                   class="form-control <?= (isset($errors['db_host']) ? 'is-invalid' : '') ?>"
                   name="db_host"
                   id="db_host"
                   required
                   value="<?= $fields['db_host'] ?>"
                   placeholder="<?= __('DB Host') ?>"
            >
            <?php
            if (isset($errors['db_host'])): ?>
            <div class="invalid-feedback"><?= implode('<br>', $errors['db_host']) ?></div>
            <?php
            endif ?>
        </div>
        <div class="form-group">
            <label for="db_port"><?= __('Port') ?></label>
            <input type="text"
                   class="form-control <?= (isset($errors['db_port']) ? 'is-invalid' : '') ?>"
                   name="db_port"
                   id="db_port"
                   required
                   value="<?= $fields['db_port'] ?>"
                   placeholder="<?= __('Port') ?>"
            >
            <?php
            if (isset($errors['db_port'])): ?>
            <div class="invalid-feedback"><?= implode('<br>', $errors['db_port']) ?></div>
            <?php
            endif ?>
        </div>
        <div class="form-group">
            <label for="db_name"><?= __('DB Name') ?></label>
            <input type="text"
                   class="form-control <?= (isset($errors['db_name']) ? 'is-invalid' : '') ?>"
                   name="db_name"
                   id="db_name"
                   required
                   value="<?= $fields['db_name'] ?>"
                   placeholder="<?= __('DB Name') ?>"
            >
            <?php
            if (isset($errors['db_name'])): ?>
            <div class="invalid-feedback"><?= implode('<br>', $errors['db_name']) ?></div>
            <?php
            endif ?>
        </div>
        <div class="form-group">
            <label for="db_user"><?= __('DB User') ?></label>
            <input type="text"
                   class="form-control <?= (isset($errors['db_user']) ? 'is-invalid' : '') ?>"
                   name="db_user"
                   id="db_user"
                   required
                   value="<?= $fields['db_user'] ?>"
                   placeholder="<?= __('DB User') ?>"
            >
            <?php
            if (isset($errors['db_user'])): ?>
            <div class="invalid-feedback"><?= implode('<br>', $errors['db_user']) ?></div>
            <?php
            endif ?>
        </div>
        <div class="form-group">
            <label for="db_password"><?= __('DB Password') ?></label>
            <input type="password"
                   class="form-control <?= (isset($errors['db_password']) ? 'is-invalid' : '') ?>"
                   name="db_password"
                   id="db_password"
                   value="<?= $fields['db_password'] ?>"
                   placeholder="<?= __('DB Password') ?>"
            >
            <?php
            if (isset($errors['db_password'])): ?>
            <div class="invalid-feedback"><?= implode('<br>', $errors['db_password']) ?></div>
            <?php
            endif ?>
        </div>

        <div class="mt-3">
            <button type="submit" name="submit" value="submit"
                    class="btn btn-primary" <?= $data['next_step_disabled'] ? 'disabled' : '' ?>>
                <?= $data['db_version_error'] ? __('Continue anyway') : __('Continue') ?>
            </button>
        </div>
    </form>

@endsection
