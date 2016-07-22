<?php

use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/questions', function (Request $request, Response $response, array $args) {
    $pageSize = 10;
    $pageNumber = (int) $request->getQueryParam('page', 1);
    $skip = $pageSize * ($pageNumber - 1);
    $limit = (int) $request->getQueryParam('limit', $pageSize);

    $repo = $this->get('analysisRepository');
    $questions = $repo->findAllQuestions($skip, $limit);
    $totalQuestions = $repo->countQuestions();

    $totalPages = (int) ceil($totalQuestions / $limit);

    $paginated = new PaginatedRepresentation(
        new CollectionRepresentation(
            $questions,
            'questions', // embedded rel
            'questions'  // xml element name
        ),
        $route = 'questions_get_all',
        $parameters = [],
        $pageNumber,
        $limit,
        $totalPages,
        $pageParameterName = 'page',
        $limitParameterName = 'limit',
        $generateAbsoluteUrls = false,
        $totalQuestions
    );

    $json = $this->get('hateoas')->serialize($paginated, 'json');

    return $response
        ->withHeader('Content-Type', 'application/vnd.foodapp-v1+json')
        ->write($json);
})->setName('questions_get_all');

$app->get('/questions/{id}', function (Request $request, Response $response, array $args) {
    $repo = $this->get('analysisRepository');
    $question = $repo->findQuestion($args['id']);

    $json = $this->get('hateoas')->serialize($question, 'json');

    return $response
        ->withHeader('Content-Type', 'application/vnd.osmi-v1+json')
        ->write($json);
})->setName('questions_get_one');

$app->get('/respondents', function (Request $request, Response $response, array $args) {
    $pageSize = 10;
    $pageNumber = (int) $request->getQueryParam('page', 1);
    $skip = $pageSize * ($pageNumber - 1);
    $limit = (int) $request->getQueryParam('limit', $pageSize);

    $repo = $this->get('analysisRepository');
    $respondents = $repo->findAllRespondents($skip, $limit);
    $totalQuestions = $repo->countRespondents();

    $totalPages = (int) ceil($totalQuestions / $limit);

    $paginated = new PaginatedRepresentation(
        new CollectionRepresentation(
            $respondents,
            'respondents', // embedded rel
            'respondents'  // xml element name
        ),
        $route = 'respondents_get_all',
        $parameters = [],
        $pageNumber,
        $limit,
        $totalPages,
        $pageParameterName = 'page',
        $limitParameterName = 'limit',
        $generateAbsoluteUrls = false,
        $totalQuestions
    );

    $json = $this->get('hateoas')->serialize($paginated, 'json');

    return $response
        ->withHeader('Content-Type', 'application/vnd.foodapp-v1+json')
        ->write($json);
})->setName('respondents_get_all');

$app->get('/respondents/{token}', function (Request $request, Response $response, array $args) {
    $repo = $this->get('analysisRepository');
    $person = $repo->findRespondent($args['token']);
    $json = $this->get('hateoas')->serialize($person, 'json');

    return $response
        ->withHeader('Content-Type', 'application/vnd.osmi-v1+json')
        ->write($json);
})->setName('respondents_get_one');

$app->get('/responses/{questionId}', function (Request $request, Response $response, array $args) {
    $repo = $this->get('analysisRepository');
    $surveyResponse = $repo->getSingleReponse($args['questionId']);

    $json = $this->get('hateoas')->serialize($surveyResponse, 'json');

    return $response
        ->withHeader('Content-Type', 'application/vnd.osmi-v1+json')
        ->write($json);
})->setName('responses_get_one');
