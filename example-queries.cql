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
RETURN q.question, a.answer, COUNT(p)


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
RETURN q.question, a.answer, c.name, answer_count, country_count, toString(((toFloat(answer_count) / toFloat(country_count))*100)) as perc
ORDER BY c.name ASC

// Top 10 self diagnoses WITHOUT a corresponding professional diagnosis
MATCH (selfDiagnosis:Disorder)<-[:SELF_DIAGNOSIS]-(p:Person)
WHERE NOT (p)-[:PROFESSIONAL_DIAGNOSIS]->()
RETURN selfDiagnosis.name, COUNT(p) AS diagnoses
ORDER BY diagnoses DESC
LIMIT 10;

// Top 10 Diagnoses: Self-diagnoses vs MD-diagnoses
MATCH (d:Disorder)<-[sd:SELF_DIAGNOSIS]-()
WITH d, COUNT(sd) AS selfDiagnoses
MATCH (d)<-[mdd:PROFESSIONAL_DIAGNOSIS]-()
WITH d, selfDiagnoses, COUNT(mdd) AS mdDiagnoses
RETURN d.name AS disorder, selfDiagnoses, mdDiagnoses
// Order by selfDiagnoses or mdDiagnoses, depending on preference
ORDER BY selfDiagnoses DESC
LIMIT 10;

// Incidence of self-diagnoses (WITHOUT corresponding MD diagnosis) compared to available 
// employer provided mental health coverage
// JOINs: Question to Answer.
// If ANSWERED, SELF_DIAGNOSIS, and PROFESSIONAL_DIAGNOSIS all represent pivot tables, then:
//  * Question -> Answer
//  * Person -> PersonAnswer -> Answer
//  * Person -> PersonSelfDiagnosis -> Diagnosis
//  * Person -> PersonProfessionalDiagnosis -> Diagnosis
// PROFESSIONAL_DIAGNOSIS rel would also likely represent a pivot table
//
// first we get totals so we can calculate percentages
MATCH (q:Question { field_id: 18065507 })-[:HAS_ANSWER]->(:Answer)<-[:ANSWERED]-(:Person)-[sd:SELF_DIAGNOSIS]->(d)
  WITH q, d, COUNT(sd) as total
// -->
// now do the query to get counts from each answer for each self-diagnosis
MATCH (q)-[:HAS_ANSWER]->(a)<-[:ANSWERED]-(p)-[sd:SELF_DIAGNOSIS]->(d)
  WHERE NOT (p)-[:PROFESSIONAL_DIAGNOSIS]->()
// -->
// return the question, diagnosis, answer, and perc of people who matched above with that answer
RETURN q.question, d.name, a.answer, 100.0 * COUNT(sd) / total AS percent 
ORDER BY d.name ASC, a.answer ASC, percent DESC;

