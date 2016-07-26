<?php

namespace OSMI\Survey\Graph;

use Hateoas\Hateoas;
use Psr\Http\Message\ResponseInterface;

class HALResponse
{
    public function __construct(Hateoas $hateoas, $contentType = 'application/hal+json')
    {
        $this->hateoas = $hateoas;
        $this->contentType = $contentType;
    }

    public function withJson(ResponseInterface $response, $data, $status = 200)
    {
        $hal = $this->hateoas->serialize($data, 'json');
        // TODO: Gotta find a better way to generate etags.
        $etag = md5($hal);

        return $response
            ->withHeader('Content-Type', $this->contentType)
            ->withHeader('ETag', sprintf('W/"%s"', $etag))
            ->withHeader('Vary', 'If-None-Match, Accept')
            ->withHeader('Cache-Control', 'private, must-revalidate')
            ->withStatus($status)
            ->write($hal);
    }
}
