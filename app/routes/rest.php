<?php

use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->group('/questions', function () {
    $this->get('', function (Request $request, Response $response, array $args) {
        $pageSize = 10;
        $pageNumber = (int) $request->getQueryParam('page', 1);
        $skip = $pageSize * ($pageNumber - 1);
        $limit = (int) $request->getQueryParam('limit', $pageSize);

        $repo = $this->get('analysisRepository');
        $questions = $repo->findAllQuestions($skip, $limit);
        $totalQuestions = $repo->countResources('Question');

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

        return $this->get('halResponse')
            ->withJson($response, $paginated);
    })->setName('questions_get_all');

    $this->get('/{uuid}', function (Request $request, Response $response, array $args) {
        $repo = $this->get('analysisRepository');
        $question = $repo->findQuestion($args['uuid']);

        return $this->get('halResponse')
            ->withJson($response, $question);
    })->setName('questions_get_one');

    $this->get('/{uuid}/answers', function (Request $request, Response $response, array $args) {
        $pageSize = 100;
        $pageNumber = (int) $request->getQueryParam('page', 1);
        $skip = $pageSize * ($pageNumber - 1);
        $limit = (int) $request->getQueryParam('limit', $pageSize);

        $repo = $this->get('analysisRepository');
        $resources = $repo->findAllAnswersByQuestion($args['uuid']);
        $totalResources = count($resources);
        $totalPages = (int) ceil($totalResources / $limit);

        $paginated = new PaginatedRepresentation(
            new CollectionRepresentation(
                $resources,
                'answers', // embedded rel
                'answers'  // xml element name
            ),
            $route = 'question_answers',
            $parameters = ['uuid' => $args['uuid']],
            $pageNumber,
            $limit,
            $totalPages,
            $pageParameterName = 'page',
            $limitParameterName = 'limit',
            $generateAbsoluteUrls = false,
            $totalResources
        );

        return $this->get('halResponse')
            ->withJson($response, $paginated);
    })->setName('question_answers');
});

$app->group('/answers/{uuid}', function () {
    $this->get('', function (Request $request, Response $response, array $args) {
        $repo = $this->get('analysisRepository');
        $resource = $repo->findAnswer($args['uuid']);

        return $this->get('halResponse')
            ->withJson($response, $resource);
    })->setName('answers_get_one');

    $this->get('/respondents', function (Request $request, Response $response, array $args) {
        $pageSize = 10;
        $pageNumber = (int) $request->getQueryParam('page', 1);
        $skip = $pageSize * ($pageNumber - 1);
        $limit = (int) $request->getQueryParam('limit', $pageSize);

        $repo = $this->get('analysisRepository');
        $resources = $repo->findAllRespondentsByAnswer($args['uuid'], $skip, $limit);
        $totalResources = $repo->countRespondentsByAnswer($args['uuid']);
        $totalPages = (int) ceil($totalResources / $limit);

        $paginated = new PaginatedRepresentation(
            new CollectionRepresentation(
                $resources,
                'respondents', // embedded rel
                'respondents'  // xml element name
            ),
            $route = 'answer_respondents',
            $parameters = ['uuid' => $args['uuid']],
            $pageNumber,
            $limit,
            $totalPages,
            $pageParameterName = 'page',
            $limitParameterName = 'limit',
            $generateAbsoluteUrls = false,
            $totalResources
        );

        return $this->get('halResponse')
            ->withJson($response, $paginated);
    })->setName('answer_respondents');
});

$app->group('/respondents', function () {
    $this->get('', function (Request $request, Response $response, array $args) {
        $pageSize = 10;
        $pageNumber = (int) $request->getQueryParam('page', 1);
        $skip = $pageSize * ($pageNumber - 1);
        $limit = (int) $request->getQueryParam('limit', $pageSize);

        $repo = $this->get('analysisRepository');
        $respondents = $repo->findAllRespondents($skip, $limit);
        $totalQuestions = $repo->countResources('Person');

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

        return $this->get('halResponse')
            ->withJson($response, $paginated);
    })->setName('respondents_get_all');

    $this->get('/{uuid}', function (Request $request, Response $response, array $args) {
        $repo = $this->get('analysisRepository');
        $person = $repo->findRespondent($args['uuid']);

        return $this->get('halResponse')
            ->withJson($response, $person);
    })->setName('respondents_get_one');

    $this->get('/{uuid}/answers', function (Request $request, Response $response, array $args) {
        $pageSize = 10;
        $pageNumber = (int) $request->getQueryParam('page', 1);
        $skip = $pageSize * ($pageNumber - 1);
        $limit = (int) $request->getQueryParam('limit', $pageSize);

        $repo = $this->get('analysisRepository');
        $resources = $repo->findAllAnswersByRespondent($args['uuid'], $skip, $limit);
        $totalResources = $repo->countAnswersByRespondent($args['uuid']);
        $totalPages = (int) ceil($totalResources / $limit);

        $paginated = new PaginatedRepresentation(
            new CollectionRepresentation(
                $resources,
                'answers', // embedded rel
                'answers'  // xml element name
            ),
            $route = 'respondent_answers',
            $parameters = ['uuid' => $args['uuid']],
            $pageNumber,
            $limit,
            $totalPages,
            $pageParameterName = 'page',
            $limitParameterName = 'limit',
            $generateAbsoluteUrls = false,
            $totalResources
        );

        return $this->get('halResponse')
            ->withJson($response, $paginated);
    })->setName('respondent_answers');
});
