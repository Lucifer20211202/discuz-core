<?php
declare(strict_types=1);

/**
 *      Discuz & Tencent Cloud
 *      This is NOT a freeware, use is subject to license terms
 *
 *      Id: Policy.php 28830 2019-10-10 15:34 chenkeke $
 */

namespace Discuz\Contracts\Policy;

use Flarum\Event\GetPermission;
use Illuminate\Contracts\Events\Dispatcher;

interface Policy
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events);

    /**
     * @param GetPermission $event
     * @return bool|void
     */
    public function getPermission($event);

    /**
     * @param ScopeModelVisibility $event
     */
    public function scopeModelVisibility($event);

}