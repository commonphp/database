<?php

namespace Neuron\Database;

use Attribute;
use Neuron\Extensibility\AbstractExtension;
use Neuron\Extensibility\ExtensionType;

#[Attribute(Attribute::TARGET_CLASS)]
#[ExtensionType(singleton: false, requireInterface: DatabaseDriverInterface::class)]
class DatabaseDriver extends AbstractExtension
{

}