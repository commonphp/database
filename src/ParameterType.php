<?php

namespace Neuron\Database;

/**
 * Provides different paramater types in enum format
 */
enum ParameterType
{
    /** These are named parameters */
    case Named;

    /** These are positional (n-based) parameters */
    case Positional;
}