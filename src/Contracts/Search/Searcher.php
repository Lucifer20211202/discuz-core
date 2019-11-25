<?php

/**
 * Discuz & Tencent Cloud
 * This is NOT a freeware, use is subject to license terms
 */

namespace Discuz\Contracts\Search;

interface Searcher
{
    public function apply(Search $search);

    public function search();

    public function conditions(array $condition = []);

    public function getSingle($reset = false);

    public function getMultiple($reset = false);

    public function getIncludes();
}
