<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2024-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\OroPlugin\Search;

use Gally\Sdk\GraphQl\Response;

/**
 * Search registry.
 */
class SearchRegistry
{
    private Response $response;
    private ?string $priceFilterUnit = null;

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    public function getPriceFilterUnit(): ?string
    {
        return $this->priceFilterUnit;
    }

    public function setPriceFilterUnit(string $priceFilterUnit): void
    {
        $this->priceFilterUnit = $priceFilterUnit;
    }
}
