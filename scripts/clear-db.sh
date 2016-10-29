#!/bin/bash

SCRIPT_DIR=`dirname $0`
PROJECT_DIR="$SCRIPT_DIR/.."
cd $PROJECT_DIR

# Assumes theres a Neo4j instance running in this directory
$PROJECT_DIR/neo4j/bin/neo4j stop
rm -rf $PROJECT_DIR/neo4j/data/databases/graph.db/*
$PROJECT_DIR/neo4j/bin/neo4j start
