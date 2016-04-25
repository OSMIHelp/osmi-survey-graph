# 2016 OSMI Survey Graph

* Copy `.env.example`
* Update the `GRAPH_URL`
* Run `php -f scripts/load-data.php`
* Enjoy!

## Queries

Single response
```
MATCH (q:Question { id: { questionId }})-[:HAS_ANSWER]->(a)<-[:ANSWERED]-()
WITH q, a, COUNT(*) AS responses
RETURN q.question AS question, COLLECT({ answer: a.answer , responses: responses }) AS answers;
```

Paged responses
```
MATCH (q:Question)
WITH q
ORDER BY q.order
SKIP 0
LIMIT 10
MATCH (q)-[:HAS_ANSWER]->(a)<-[:ANSWERED]-(p)
RETURN q.order, q.question, a.answer, COUNT(*) AS responses
ORDER BY q.order, responses DESC;
```

Who works somewhere other than they live, and where are those places?
```
MATCH (works)<-[:WORKS_IN]-(person:Person)-[:LIVES_IN]->(lives)
WHERE works <> lives
RETURN person, works, lives;
```
