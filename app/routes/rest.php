<?php

use OSMI\Survey\Graph\Enum\Diagnosis;
use OSMI\Survey\Graph\Helper\Paginator;
use OSMI\Survey\Graph\Model\Disorder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->group('/questions', function () {
    $this->get('', function (Request $request, Response $response, array $args) {
        $pageSize = 10;
        $pageNumber = (int) $request->getQueryParam('page', 1);
        $skip = $pageSize * ($pageNumber - 1);
        $limit = (int) $request->getQueryParam('limit', $pageSize);

        $repo = $this->get('analysisRepository');
        $resources = $repo->findAllQuestions($skip, $limit);
        $totalResources = $repo->countResources('Question');

        $paginated = Paginator::createPaginatedRepresentation(
            $pageNumber,
            $limit,
            $resources,
            $totalResources,
            'questions_get_all',
            $routeParams = [],
            'questions'
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

        $paginated = Paginator::createPaginatedRepresentation(
            $pageNumber,
            $limit,
            $resources,
            $totalResources,
            'question_answers',
            $parameters = ['uuid' => $args['uuid']],
            'answers'
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

        $paginated = Paginator::createPaginatedRepresentation(
            $pageNumber,
            $limit,
            $resources,
            $totalResources,
            'answer_respondents',
            $parameters = ['uuid' => $args['uuid']],
            'respondents'
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
        $resources = $repo->findAllRespondents($skip, $limit);
        $totalResources = $repo->countResources('Person');

        $paginated = Paginator::createPaginatedRepresentation(
            $pageNumber,
            $limit,
            $resources,
            $totalResources,
            'respondents_get_all',
            $parameters = [],
            'respondents'
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

        $paginated = Paginator::createPaginatedRepresentation(
            $pageNumber,
            $limit,
            $resources,
            $totalResources,
            'respondent_answers',
            $parameters = ['uuid' => $args['uuid']],
            'answers'
        );

        return $this->get('halResponse')
            ->withJson($response, $paginated);
    })->setName('respondent_answers');

    $this->get('/{uuid}/disorders', function (Request $request, Response $response, array $args) {
        $pageSize = 10;
        $pageNumber = (int) $request->getQueryParam('page', 1);
        $skip = $pageSize * ($pageNumber - 1);
        $limit = (int) $request->getQueryParam('limit', $pageSize);

        $repo = $this->get('analysisRepository');
        $type = $request->getQueryParam('type', null);

        if ($type !== null) {
            $type = new Diagnosis($type);
        }

        $resources = $repo->findDisordersByRespondent(
            $args['uuid'],
            $type,
            $skip,
            $limit
        );
        $totalResources = $repo->countDisordersByRespondent($args['uuid'], $type);

        $paginated = Paginator::createPaginatedRepresentation(
            $pageNumber,
            $limit,
            $resources,
            $totalResources,
            'respondent_get_disorders',
            $parameters = [
                'uuid' => $args['uuid'],
                'type' => ($type === null) ? $type : (string) $type,
            ],
            'disorders'
        );

        return $this->get('halResponse')
            ->withJson($response, $paginated);
    })->setName('respondent_get_disorders');
});

$app->group('/disorders', function () {
    $this->get('', function (Request $request, Response $response, array $args) {
        $pageSize = 10;
        $pageNumber = (int) $request->getQueryParam('page', 1);
        $skip = $pageSize * ($pageNumber - 1);
        $limit = (int) $request->getQueryParam('limit', $pageSize);

        $repo = $this->get('analysisRepository');
        $resources = $repo->findAllDisorders($skip, $limit);
        $totalResources = $repo->countResources('Disorder');

        $paginated = Paginator::createPaginatedRepresentation(
            $pageNumber,
            $limit,
            $resources,
            $totalResources,
            'disorders_get_all',
            $parameters = [],
            'disorders'
        );

        return $this->get('halResponse')
            ->withJson($response, $paginated);
    })->setName('disorders_get_all');

    $this->get('/{uuid}', function (Request $request, Response $response, array $args) {
        $repo = $this->get('analysisRepository');
        $question = $repo->findDisorder($args['uuid']);

        return $this->get('halResponse')
            ->withJson($response, $question);
    })->setName('disorders_get_one');

    $this->get('/{uuid}/respondents', function (Request $request, Response $response, array $args) {
        $pageSize = 10;
        $pageNumber = (int) $request->getQueryParam('page', 1);
        $skip = $pageSize * ($pageNumber - 1);
        $limit = (int) $request->getQueryParam('limit', $pageSize);

        $repo = $this->get('analysisRepository');
        $type = $request->getQueryParam('type', null);

        if ($type !== null) {
            $type = new Diagnosis($type);
        }

        $resources = $repo->findRespondentsByDisorder(
            $args['uuid'],
            $type,
            $skip,
            $limit
        );
        $totalResources = $repo->countRespondentsByDisorder($args['uuid'], $type);

        $paginated = Paginator::createPaginatedRepresentation(
            $pageNumber,
            $limit,
            $resources,
            $totalResources,
            'disorder_get_respondents',
            $parameters = [
                'uuid' => $args['uuid'],
                'type' => ($type === null) ? $type : (string) $type,
            ],
            'respondents'
        );

        return $this->get('halResponse')
            ->withJson($response, $paginated);
    })->setName('disorder_get_respondents');
});
