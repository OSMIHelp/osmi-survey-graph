#!/usr/bin/env bash

SCRIPT_DIR=`dirname $0`
PROJECT_DIR="$SCRIPT_DIR/.."
cd $PROJECT_DIR

echo "Clearing DB..."
echo "============================================================================"
# Assumes theres a Neo4j instance running in this directory
$PROJECT_DIR/neo4j/bin/neo4j stop
rm -rf $PROJECT_DIR/neo4j/data/databases/graph.db/*
$PROJECT_DIR/neo4j/bin/neo4j start
echo "Done."

echo "Clearing saved JSON data..."
echo "============================================================================"
`rm $PROJECT_DIR/data/*`
echo "Done."

echo "Retrieving new JSON data from Typeform..."
echo "============================================================================"
$PROJECT_DIR/scripts/retrieve-data.php
echo "Done."

echo "Importing saved JSON data intro graph..."
echo "============================================================================"
$PROJECT_DIR/scripts/load-data.php
echo "Done."

echo "Done importing."
