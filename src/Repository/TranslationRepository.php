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

namespace Gally\OroPlugin\Repository;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Gally translation repository.
 */
class TranslationRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getTranslationsForKeys(array $keys): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('tk.key AS translation_key, t.value, l.code AS language_code')
            ->from('Oro\Bundle\TranslationBundle\Entity\Translation', 't')
            ->innerJoin('Oro\Bundle\TranslationBundle\Entity\TranslationKey', 'tk', 'WITH', 'tk.id = t.translationKey')
            ->innerJoin('Oro\Bundle\TranslationBundle\Entity\Language', 'l', 'WITH', 'l.id = t.language')
            ->where('tk.key IN (:keys)')
            ->setParameter('keys', $keys);

        return $queryBuilder->getQuery()->getArrayResult();
    }
}
