#!/bin/bash

# Assumes theres a Neo4j instance running in this directory
./neo4j/bin/neo4j stop
rm -rf ./neo4j/data/graph.db/*
./neo4j/bin/neo4j start
