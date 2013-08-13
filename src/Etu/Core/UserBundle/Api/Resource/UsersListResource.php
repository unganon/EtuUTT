<?php

namespace Etu\Core\UserBundle\Api\Resource;

use Etu\Api\Framework\Resource;
use Etu\Api\Http\Response;
use Etu\Api\Sorting;

// Annotations
use Etu\Api\Annotations as Api;
use Swagger\Annotations as SWG;

/**
 * @SWG\Resource(resourcePath="users")
 *
 * @Api\Resource(
 *      "/users/{sortExpression}/{page}",
 *      defaults={"sortExpression"="lastname:asc,firstname:asc", "page"=1}
 * )
 */
class UsersListResource extends Resource
{
	/**
	 * @SWG\Api(
	 *      path="/users/{sortExpression}/{page}",
	 *      description="Operations about users",
	 *      @SWG\Operations(
	 *          @SWG\Operation(
	 *              httpMethod="GET",
	 *              summary="List all the users",
	 *              notes="Return a paginated list of all the users using a sorting expression",
	 *              responseClass="User",
	 *              nickname="getAllUsers",
	 *
	 *              @SWG\Parameters(
	 *                  @SWG\Parameter(
	 *                      name="sortExpression",
	 *                      description="Expression to sort users. See documentation about SortingExpressions.",
	 *                      paramType="path",
	 *                      required="false",
	 *                      allowMultiple="false",
	 *                      defaultValue="lastname:asc,firstname:asc",
	 *                      dataType="string"
	 *                  )
	 *              ),
	 *              @SWG\Parameters(
	 *                  @SWG\Parameter(
	 *                      name="page",
	 *                      description="Page number",
	 *                      paramType="path",
	 *                      required="false",
	 *                      allowMultiple="false",
	 *                      defaultValue="1",
	 *                      dataType="int"
	 *                  )
	 *              )
	 *          )
	 *      )
	 * )
	 *
	 * @Api\Operation(method="GET")
	 */
	public function getOperation($sortExpression, $page)
	{
		$orderBy = Sorting\Expression::getOrderBy($sortExpression, array(
			'firstname', 'lastname', 'fullname', 'level'
		));

		if ($orderBy === false) {
			return Response::error(Response::NOT_ACCEPTABLE, 'Invalid sort expression');
		}

		if (empty($orderBy)) {
			$orderBy['lastname'] = 'ASC';
			$orderBy['firstname'] = 'ASC';
		}

		if ($this->getCache()->contains('etuutt_api_users_list_'.$page)) {
			$cache = $this->getCache()->fetch('etuutt_api_users_list_'.$page);
			$count = $cache['count'];
			$users = $cache['users'];
		} else {
			$count = $this->getDoctrine()->createQueryBuilder()
				->select('COUNT(*) AS count')
				->from('etu_users', 'u')
				->execute()
				->fetch();

			$results = $this->getDoctrine()->createQueryBuilder()
				->select('
					u.id, u.login, u.studentId, u.mail, u.firstName, u.lastName, u.formation,
					u.niveau AS level, u.filiere AS speciality, u.isStudent, u.avatar AS picture,
					u.website, u.facebook, u.twitter, u.linkedin, u.viadeo
				')
				->from('etu_users', 'u')
				->setFirstResult(($page - 1) * 20)
				->setMaxResults(20)
				->execute()
				->fetchAll();

			$users = array();

			foreach ($results as $user) {
				$users[$user['login']] = $user;
				$users[$user['login']]['isStudent'] = (bool) $user['isStudent'];
				$users[$user['login']]['formation'] = ($user['formation'] == 'Nc') ? null : $user['formation'];
			}

			$this->getCache()->save('etuutt_api_users_list_'.$page, array(
				'count' => $count,
				'users' => $users,
			));
		}

		$totalItems = (int) $count['count'];
		$totalPages = ceil($totalItems / 20);

		$previous = false;
		$next = false;

		if ($page > 1) {
			$previous = $this->generateUrl('users_list', array(
				'sortExpression' => $sortExpression,
				'page' => $page - 1,
			));
		}

		if ($page < $totalPages) {
			$next = $this->generateUrl('users_list', array(
				'sortExpression' => $sortExpression,
				'page' => $page + 1,
			));
		}

		return array(
			'paging' => array(
				'current' => $page,
				'previous' => $previous,
				'next' => $next,
				'totalItems' => $totalItems,
				'totalPages' => $totalPages,
				'itemsPerPage' => 20,
			),
			'sort' => $orderBy,
			'users' => $users,
		);
	}
}