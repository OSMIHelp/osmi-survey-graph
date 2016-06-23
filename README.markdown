# 2016 OSMI Survey Graph

* Copy `.env.example`
* Update the `GRAPH_URL`
* Run `php -f scripts/load-data.php`
* Enjoy!

## Grabbing data from Typeform

Example using `httpie`. **We should write a script to do this.**
```
http GET https://api.typeform.com/v1/form/Ao6BTw key==<TYPEFORM_API_KEY> completed==true offset==0 > ~/osmi-survey-2016_0000.json
```

## Queries

Single response
```
MATCH (q:Question { id: "list_18065482_choice" })-[:HAS_ANSWER]->(a)<-[:ANSWERED]-(p)
RETURN a.answer AS answer, COUNT(*) AS responses
ORDER BY responses DESC;
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


## More example queries

```
// List of questions
MATCH (q:Question)
RETURN q ORDER BY q.order ASC

// A single question: "Have you ever sought treatment for a mental health issue from a mental health professional?"
MATCH (q:Question {field_id: 18068717})
RETURN q

// Question and all answers
MATCH (q:Question {field_id: 18068717})-[:HAS_ANSWER]->(a:Answer)
RETURN q, a

// Persons who have answered
MATCH (q:Question {field_id: 18068717})-[:HAS_ANSWER]->(a:Answer)<-[:ANSWERED]-(p:Person)
RETURN a, p

// Answer counts
MATCH (q:Question {field_id: 18068717})-[:HAS_ANSWER]->(a:Answer)<-[:ANSWERED]-(p:Person)
RETURN a, COUNT(p)


// Answer counts broken out by country
MATCH (q:Question {field_id: 18068717})-[:HAS_ANSWER]->(a:Answer)<-[:ANSWERED]-(p:Person)-[:LIVES_IN_COUNTRY]->(c:Country)
RETURN q.question, a.answer, c.name, count(p) as count_country_answer
ORDER BY c.name

// Answer counts with percentages
MATCH (q:Question {field_id: 18068717})
WITH q
MATCH (q)-[:HAS_ANSWER]->()<-[ra:ANSWERED]-(p)-[:LIVES_IN_COUNTRY]-(c:Country)
WITH q, c, count(ra) as country_count
MATCH (q)-[:HAS_ANSWER]->(a:Answer)<-[:ANSWERED]-(p:Person)-[:LIVES_IN_COUNTRY]-(c)
WITH q, a, c, count(p) as answer_count, country_count
RETURN q, a.answer, c.name, answer_count, country_count, toString(((toFloat(answer_count) / toFloat(country_count))*100)) as perc
ORDER BY c.name ASC
```
