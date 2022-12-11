<?php

$fields = $data['fields'] ?? [];
$errors = $data['errors'] ?? [];

?>
@extends('install::layout')
@section('content')

    <h4 class="mb-2 mt-3 fw-bold"><?= __('Website settings') ?></h4>

    <?php
    if (! empty($errors['unknown'])): ?>
    <div class="alert alert-danger"><?= implode('<br>', $errors['unknown']) ?></div>
    <?php
    endif ?>

    <form action="/install/?step=4" method="post" class="mb-3">
        <div class="form-group">
            <label for="home_url"><?= __('Site address') ?></label>
            <input type="text"
                   class="form-control <?= (isset($errors['home_url']) ? 'is-invalid' : '') ?>"
                   name="home_url"
                   id="home_url"
                   required
                   value="<?= $fields['home_url'] ?>"
                   placeholder="<?= __('Site address') ?>"
            >
            <?php
            if (isset($errors['home_url'])): ?>
            <div class="invalid-feedback"><?= implode('<br>', $errors['home_url']) ?></div>
            <?php
            endif ?>
        </div>
        <div class="form-group">
            <label for="email"><?= __('E-mail') ?></label>
            <input type="email"
                   class="form-control <?= (isset($errors['email']) ? 'is-invalid' : '') ?>"
                   name="email"
                   id="email"
                   required
                   value="<?= $fields['email'] ?>"
                   placeholder="<?= __('E-mail') ?>"
            >
            <?php
            if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?= implode('<br>', $errors['email']) ?></div>
            <?php
            endif ?>
        </div>

        <h4 class="mb-2 mt-4 fw-bold"><?= __('Administrator data') ?></h4>
        <div class="form-group">
            <label for="admin_login"><?= __('Admin login') ?></label>
            <input type="text"
                   class="form-control <?= (isset($errors['admin_login']) ? 'is-invalid' : '') ?>"
                   name="admin_login"
                   id="admin_login"
                   required
                   value="<?= $fields['admin_login'] ?>"
                   placeholder="<?= __('Admin login') ?>"
            >
            <?php
            if (isset($errors['admin_login'])): ?>
            <div class="invalid-feedback"><?= implode('<br>', $errors['admin_login']) ?></div>
            <?php
            endif ?>
        </div>
        <div class="form-group">
            <label for="admin_password"><?= __('Admin password') ?></label>
            <input type="password"
                   class="form-control <?= (isset($errors['admin_password']) ? 'is-invalid' : '') ?>"
                   name="admin_password"
                   id="admin_password"
                   value="<?= $fields['admin_password'] ?>"
                   placeholder="<?= __('Admin password') ?>"
            >
            <?php
            if (isset($errors['admin_password'])): ?>
            <div class="invalid-feedback"><?= implode('<br>', $errors['admin_password']) ?></div>
            <?php
            endif ?>
        </div>
        <h4 class="mb-2 mt-4 fw-bold"><?= __('Additionally') ?></h4>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="form-check-input" name="install_demo" value="1" id="install_demo" <?= ! empty($fields['install_demo']) ? 'checked="checked"' : '' ?>>
            <label class="form-check-label" for="install_demo"><?= __('Install demo data') ?></label>
        </div>

        <div class="mt-3">
            <button type="submit" name="submit" value="submit" class="btn btn-primary" <?= $data['next_step_disabled'] ? 'disabled' : '' ?>><?= __('Continue') ?></button>
        </div>
    </form>
@endsection
