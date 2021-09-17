<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Admin\Controllers;

use Johncms\Container\ContainerFactory;
use Johncms\Controller\BaseController;
use Johncms\View\AdminRenderEngineFactory;
use Johncms\View\Extension\AdminAssets;
use Johncms\View\Extension\Assets;
use Johncms\View\Render;
use PDO;

class BaseAdminController extends BaseController
{
    public function __construct()
    {
        $container = ContainerFactory::getContainer();
        $container->setFactory(Assets::class, AdminAssets::class);
        $container->setFactory(Render::class, AdminRenderEngineFactory::class);
        parent::__construct();

        $this->translator->addTranslationDomain('admin', MODULES_PATH . 'admin/locale', false);

        $db = di(PDO::class);
        $this->render->addData(
            [
                'regtotal'   => $db->query("SELECT COUNT(*) FROM `users` WHERE `preg`='0'")->fetchColumn(),
                'countusers' => $db->query("SELECT COUNT(*) FROM `users` WHERE `preg`='1'")->fetchColumn(),
                'countadm'   => $db->query("SELECT COUNT(*) FROM `users` WHERE `rights` >= '1'")->fetchColumn(),
                'bantotal'   => $db->query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `ban_time` > '" . time() . "'")->fetchColumn(),
            ],
            [
                'system::app/sidebar-admin-menu',
            ]
        );
        $this->navChain->add(d__('admin', 'Admin Panel'), '/admin/');
    }
}
