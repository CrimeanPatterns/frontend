#!/usr/bin/env python3
"""
SQL Log Parser - Extracts SELECT statements from a TSV log file.

Usage:
    python parse_sql_log.py <input_file> <output_file>

This script parses TSV-formatted SQL logs and extracts only the SELECT statements,
writing them to an output file with one statement per line, each ending with a semicolon.
"""

import sys
import re
import csv

# Increase CSV field size limit to handle large SQL statements
csv.field_size_limit(sys.maxsize)  # Use system's maximum size limit

def extract_select_statements(input_file, output_file):
    """
    Extract SELECT statements from a TSV file and write them to an output file.

    Args:
        input_file (str): Path to the input TSV file
        output_file (str): Path to the output text file
    """
    select_statements = []

    # Try different encodings
    encodings = ['utf-8', 'latin-1', 'cp1252', 'ascii', 'utf-16']
    success = False

    for encoding in encodings:
        try:
            print(f"Trying encoding: {encoding}")
            with open(input_file, 'r', encoding=encoding) as f:
                # Skip the header line
                next(f)

                # Use csv.reader with tab delimiter to properly handle TSV format
                tsv_reader = csv.reader(f, delimiter='\t')

                for row in tsv_reader:
                    # Check if this is a valid row with enough columns
                    if len(row) >= 6:
                        command_type = row[4].strip() if len(row) > 4 else ""
                        sql_statement = row[5].strip() if len(row) > 5 else ""

                        # Check if this is a Query command and contains a SELECT statement
                        if command_type == "Query" and sql_statement.upper().lstrip().startswith("SELECT"):
                            # Skip if it contains "FOR UPDATE" (case insensitive)
                            if "for update" in sql_statement.lower():
                                continue

                            # Skip if it contains "performance_schema" (case insensitive)
                            if "performance_schema" in sql_statement.lower():
                                continue

                            # Skip if it contains "rds_heartbeat2" (case insensitive)
                            if "rds_heartbeat2" in sql_statement.lower():
                                continue

                            # Skip if it contains "INFORMATION_SCHEMA" (case insensitive)
                            if "information_schema" in sql_statement.lower():
                                continue

                            # Skip if it contains "mysql" database (case insensitive)
                            # Use word boundary to avoid matching partial words
                            if re.search(r'\bmysql\b', sql_statement.lower()):
                                continue

                            # Clean up the SQL statement
                            # Replace literal '\n' and '\t' strings with spaces
                            cleaned_sql = sql_statement.replace('\\n', ' ').replace('\\t', ' ')
                            # Also replace actual newlines and tabs
                            cleaned_sql = cleaned_sql.replace('\n', ' ').replace('\t', ' ').replace('\r', ' ')
                            # Then normalize all whitespace sequences to a single space
                            cleaned_sql = re.sub(r'\s+', ' ', cleaned_sql)

                            # Count parentheses to ensure they match
                            open_parens = cleaned_sql.count('(')
                            close_parens = cleaned_sql.count(')')

                            # Skip statements with unbalanced parentheses
                            if open_parens != close_parens:
                                print(f"Skipping statement with unbalanced parentheses: {cleaned_sql[:50]}...")
                                continue

                            # Check and add LIMIT 10 if needed - but only to the outer query
                            # First check if this is a statement with subqueries
                            has_subquery = "select" in cleaned_sql.lower()[1:]  # Check after the first character

                            if has_subquery:
                                # For statements with subqueries, don't try to modify the LIMIT
                                # as it's too complex to handle correctly here
                                pass
                            else:
                                # Case 1: No LIMIT exists
                                if not re.search(r'\bLIMIT\b\s+\d+', cleaned_sql, re.IGNORECASE):
                                    cleaned_sql += " LIMIT 10"
                                else:
                                    # Case 2: LIMIT exists but is greater than 10
                                    limit_match = re.search(r'\bLIMIT\b\s+(\d+)', cleaned_sql, re.IGNORECASE)
                                    if limit_match:
                                        try:
                                            limit_value = int(limit_match.group(1))
                                            if limit_value > 10:
                                                # Replace the existing LIMIT with LIMIT 10
                                                cleaned_sql = re.sub(r'\bLIMIT\b\s+\d+', 'LIMIT 10', cleaned_sql, flags=re.IGNORECASE)
                                        except (ValueError, IndexError):
                                            # If we can't parse the limit value, just leave it as is
                                            print(f"Warning: Could not parse LIMIT value in: {cleaned_sql}")

                            # Add to our list
                            select_statements.append(cleaned_sql)

            # If we got here without an exception, the encoding worked
            print(f"Successfully read file with {encoding} encoding")
            success = True
            break

        except UnicodeDecodeError:
            # If this encoding failed, try the next one
            print(f"Failed to decode with {encoding} encoding")

    # If all encodings failed, try a binary approach
    if not success:
        print("All text encodings failed. Trying binary approach...")
        try:
            with open(input_file, 'rb') as f:
                # Skip the header line
                next(f)

                for line in f:
                    try:
                        # Try to decode the line with latin-1 which can handle any byte
                        decoded_line = line.decode('latin-1')
                        # Split by tabs
                        parts = decoded_line.split('\t')

                        if len(parts) >= 6:
                            command_type = parts[4].strip()
                            sql_statement = parts[5].strip()

                            if command_type == "Query" and sql_statement.upper().lstrip().startswith("SELECT"):
                                # Skip if it contains "FOR UPDATE" (case insensitive)
                                if "for update" in sql_statement.lower():
                                    continue

                                # Skip if it contains "performance_schema" (case insensitive)
                                if "performance_schema" in sql_statement.lower():
                                    continue

                                # Skip if it contains "rds_heartbeat2" (case insensitive)
                                if "rds_heartbeat2" in sql_statement.lower():
                                    continue

                                # Skip if it contains "INFORMATION_SCHEMA" (case insensitive)
                                if "information_schema" in sql_statement.lower():
                                    continue

                                # Skip if it contains "mysql" database (case insensitive)
                                # Use word boundary to avoid matching partial words
                                if re.search(r'\bmysql\b', sql_statement.lower()):
                                    continue

                                # Clean up the SQL statement
                                # Replace literal '\n' and '\t' strings with spaces
                                cleaned_sql = sql_statement.replace('\\n', ' ').replace('\\t', ' ')
                                # Also replace actual newlines and tabs
                                cleaned_sql = cleaned_sql.replace('\n', ' ').replace('\t', ' ').replace('\r', ' ')
                                # Then normalize all whitespace sequences to a single space
                                cleaned_sql = re.sub(r'\s+', ' ', cleaned_sql)

                                # Count parentheses to ensure they match
                                open_parens = cleaned_sql.count('(')
                                close_parens = cleaned_sql.count(')')

                                # Skip statements with unbalanced parentheses
                                if open_parens != close_parens:
                                    print(f"Skipping statement with unbalanced parentheses: {cleaned_sql[:50]}...")
                                    continue

                                # Check and add LIMIT 10 if needed - but only to the outer query
                                # First check if this is a statement with subqueries
                                has_subquery = "select" in cleaned_sql.lower()[1:]  # Check after the first character

                                if has_subquery:
                                    # For statements with subqueries, don't try to modify the LIMIT
                                    # as it's too complex to handle correctly here
                                    pass
                                else:
                                    # Case 1: No LIMIT exists
                                    if not re.search(r'\bLIMIT\b\s+\d+', cleaned_sql, re.IGNORECASE):
                                        cleaned_sql += " LIMIT 10"
                                    else:
                                        # Case 2: LIMIT exists but is greater than 10
                                        limit_match = re.search(r'\bLIMIT\b\s+(\d+)', cleaned_sql, re.IGNORECASE)
                                        if limit_match and int(limit_match.group(1)) > 10:
                                            # Replace the existing LIMIT with LIMIT 10
                                            cleaned_sql = re.sub(r'\bLIMIT\b\s+\d+', 'LIMIT 10', cleaned_sql, flags=re.IGNORECASE)
                                select_statements.append(cleaned_sql)
                    except Exception as e:
                        print(f"Error processing binary line: {e}")
                        continue

            print("Binary approach completed")
        except Exception as e:
            print(f"Binary approach failed: {e}")

    # Write out all SELECT statements to the output file
    try:
        with open(output_file, 'w', encoding='utf-8') as out_f:
            for statement in select_statements:
                # Ensure the statement ends with a semicolon
                if not statement.endswith(';'):
                    statement += ';'
                out_f.write(f"{statement}\n")

        print(f"Successfully extracted {len(select_statements)} SELECT statements to {output_file}")

    except Exception as e:
        print(f"Error writing output file: {e}", file=sys.stderr)
        sys.exit(1)

if __name__ == "__main__":
    # Check command line arguments
    if len(sys.argv) != 3:
        print(f"Usage: {sys.argv[0]} <input_file> <output_file>", file=sys.stderr)
        sys.exit(1)

    input_file = sys.argv[1]
    output_file = sys.argv[2]

    try:
        extract_select_statements(input_file, output_file)
    except Exception as e:
        print(f"Unexpected error: {e}", file=sys.stderr)
        sys.exit(1)