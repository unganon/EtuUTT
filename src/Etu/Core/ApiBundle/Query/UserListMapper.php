<?php

namespace Etu\Core\ApiBundle\Query;

use Doctrine\ORM\QueryBuilder;
use Etu\Core\ApiBundle\Framework\Query\QueryMapper;
use Symfony\Component\HttpFoundation\ParameterBag;

class UserListMapper extends QueryMapper
{
    public function mapQuery(QueryBuilder $query, ParameterBag $request)
    {
        if ($request->has('firstname')) {
            $query->andWhere('u.firstName LIKE :firstname')
                ->setParameter('firstname', '%'.$request->get('firstname').'%');
        }

        if ($request->has('lastname')) {
            $query->andWhere('u.lastName LIKE :lastname')
                ->setParameter('lastname', '%'.$request->get('lastname').'%');
        }

        if ($request->has('branch')) {
            $query->andWhere('u.branch = :branch')
                ->setParameter('branch', $request->get('branch'));
        }

        if ($request->has('level')) {
            $query->andWhere('u.niveau = :level')
                ->setParameter('level', $request->get('level'));
        }

        if ($request->has('speciality')) {
            $query->andWhere('u.filiere = :speciality')
                ->setParameter('speciality', $request->get('speciality'));
        }

        if ($request->has('is_student')) {
            $query->andWhere('u.isStudent = :is_student')
                ->setParameter('is_student', (bool) $request->get('is_student'));
        }

        if ($request->has('bde_member')) {
            if ($request->get('bde_member') == '1') {
                $query->andWhere('m.end > NOW()');
            } else {
                $query->andWhere('m.end < NOW()');
            }
        }

        return $query;
    }
}