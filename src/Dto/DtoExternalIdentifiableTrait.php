<?php

/*
 * This file is part of By Night.
 * (c) 2013-2021 Guillaume Sainthillier <guillaume.sainthillier@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Dto;

trait DtoExternalIdentifiableTrait
{
    /** @var string|null */
    public $externalId;

    /** @var string|null */
    public $externalOrigin;

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function getExternalOrigin(): ?string
    {
        return $this->externalOrigin;
    }
}
