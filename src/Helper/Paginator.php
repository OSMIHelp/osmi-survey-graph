<?php

namespace OSMI\Survey\Graph\Helper;

use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;

class Paginator
{
    /**
     * Creates paginated representation of resources.
     *
     * @param int    $pageNumber
     * @param int    $limit                Max items per page
     * @param mixed  $resources            Collection of items to paginate
     * @param int    $totalResources       Total number of resources
     * @param string $routeName            Named Slim route
     * @param array  $routeParams          Named route params and query string args
     * @param string $relName              Embedded collection relationship name
     * @param bool   $generateAbsoluteUrls
     * @param string $pageParameterName
     * @param string $limitParameterName
     *
     * @return PaginatedRepresentation
     */
    public static function createPaginatedRepresentation(
        $pageNumber,
        $limit,
        $resources,
        $totalResources,
        $routeName,
        $routeParams = [],
        $relName = 'items',
        $generateAbsoluteUrls = false,
        $pageParameterName = 'page',
        $limitParameterName = 'limit'
    ) {
        $totalPages = (int) ceil($totalResources / $limit);

        return new PaginatedRepresentation(
            new CollectionRepresentation(
                $resources,
                $relName, // embedded rel
                $relName  // xml element name
            ),
            $routeName,
            $routeParams,
            $pageNumber,
            $limit,
            $totalPages,
            $pageParameterName = 'page',
            $limitParameterName = 'limit',
            $generateAbsoluteUrls = false,
            $totalResources
        );
    }
}
