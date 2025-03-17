<?php

namespace Neuron\Database;

/**
 * An enum of PDO-compliant fetch modes
 */
enum FetchMode: int
{
    /** Specifies that the fetch method shall return each row as an object with variable names that correspond to the column names returned in the result set. */
    case FETCH_LAZY = 1;

    /** Specifies that the fetch method shall return each row as an array indexed by column name as returned in the corresponding result set. */
    case FETCH_ASSOC = 2;

    /** Specifies that the fetch method shall return each row as an array indexed by column number as returned in the corresponding result set, starting at column 0. */
    case FETCH_NUM = 3;

    /** Specifies that the fetch method shall return each row as an array indexed by both column name and number as returned in the corresponding result set, starting at column 0. */
    case FETCH_BOTH = 4;

    /** Specifies that the fetch method shall return each row as an object with property names that correspond to the column names returned in the result set. */
    case FETCH_OBJ = 5;

    /** Specifies that the fetch method shall return TRUE and assign the values of the columns in the result set to the PHP variables to which they were bound with the PDOStatement::bindParam or PDOStatement::bindColumn methods. */
    case FETCH_BOUND = 6;

    /** Specifies that the fetch method shall return only a single requested column from the next row in the result set. */
    case FETCH_COLUMN = 7;

    /** Specifies that the fetch method shall return a new instance of the requested class, mapping the columns to named properties in the class. The magic __set method is called if the property doesn't exist in the requested class */
    case FETCH_CLASS = 8;

    /** Specifies that the fetch method shall update an existing instance of the requested class, mapping the columns to named properties in the class. */
    case FETCH_INTO = 9;

    /** Allows completely customize the way data is treated on the fly (only valid inside PDOStatement::fetchAll). */
    case FETCH_FUNC = 10;

    /** Specifies that the fetch method shall return each row as an array indexed by column name as returned in the corresponding result set. If the result set contains multiple columns with the same name, PDO::FETCH_NAMED returns an array of values per column name. */
    case FETCH_NAMED = 11;

    /** Fetch a two-column result into an array where the first column is a key and the second column is the value. */
    case FETCH_KEY_PAIR = 12;
}