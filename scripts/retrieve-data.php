<?php

require_once __DIR__ . '/bootstrap.php';

$filename = dirname(__DIR__) . '/data/osmi-survey-2016_' . time() . '.json';

echo "Retrieving completed responses...\n";
$surveyData = getSurveyData();
$surveyJson = json_encode($surveyData, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

echo "Writing to file {$filename}...\n";
file_put_contents($filename, $surveyJson);
echo "Done\n";

function getSurveyData($limit = 100)
{
    $offset = 0;
    $finished = false;
    $apiKey = getenv('TYPEFORM_API_KEY');

    $surveyData = [
        'stats' => [],
        'questions' => [],
        'responses' => [],
    ];
    $client = new GuzzleHttp\Client(['base_uri' => 'https://api.typeform.com/v1/']);

    while (!$finished) {
        $response = $client->get('form/Ao6BTw', [
            'query' => [
                'key' => $apiKey,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);

        $bodyContent = json_decode($response->getBody(), true);
        $surveyData['stats'] = $bodyContent['stats'];
        $surveyData['questions'] = $bodyContent['questions'];

        foreach ($bodyContent['responses'] as $response) {
            if ($response['completed'] === '1') {
                $surveyData['responses'][] = $response;
            }
        }

        // if we have fewer items than our limit, we've reached the end
        if ($bodyContent['stats']['responses']['showing'] < $limit) {
            $finished = true;
        }

        // update offset
        $offset += $limit;
    }

    return $surveyData;
}
