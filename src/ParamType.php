<?php

namespace BstCo\LaravelOpenApiHelper;

enum ParamType
{
    case MIXED;
    case BOOL;
    case INT;
    case FLOAT;
    case STRING;
    case OBJECT;
    case ARRAY;
    case CLASS_NAME;
}
