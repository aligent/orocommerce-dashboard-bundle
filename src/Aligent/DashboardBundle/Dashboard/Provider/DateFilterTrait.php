<?php
/**
 * @category  Aligent
 * @package
 * @author    Greg Ziborov <greg.ziborov@aligent.com.au>
 * @copyright 2021 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\DashboardBundle\Dashboard\Provider;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Borrowed without modification from \Oro\Bundle\MagentoBundle\Provider\DateFilterTrait
 *
 * Provides applyDateFiltering(QueryBuilder $qb, $field, \DateTime $start = null, \DateTime $end = null) helper method.
 */
trait DateFilterTrait
{
    /**
     * @param QueryBuilder   $qb
     * @param string $field
     * @param DateTime|null $start
     * @param DateTime|null $end
     * @return void
     */
    protected function applyDateFiltering(
        QueryBuilder $qb,
        string       $field,
        DateTime     $start = null,
        DateTime     $end = null
    ): void {
        if ($start) {
            $qb
                ->andWhere(QueryBuilderUtil::sprintf('%s >= :start', $field))
                ->setParameter('start', $start, Types::DATETIME_MUTABLE);
        }
        if ($end) {
            $qb
                ->andWhere(QueryBuilderUtil::sprintf('%s <= :end', $field))
                ->setParameter('end', $end, Types::DATETIME_MUTABLE);
        }
    }
}
