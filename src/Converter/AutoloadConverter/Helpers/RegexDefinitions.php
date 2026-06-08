<?php

namespace App\Converter\Helpers;
/**
 * @author Selcuk Mart
 * 20.07.2022
 * 09:45
 */
class RegexDefinitions
{

    /**
     * https://regex101.com/r/dIBh1c/2
     */
    public const CLASS_REGEX = '@^class\s+(\w+)@mx';

    public const INTERFACE_REGEX = '@^interface\s+(\w+)@mx';

    public const ABSTRACT_REGEX = '@^abstract\s+class\s+(\w+)@mx';

    public const FINAL_REGEX = '@^final\s+class\s+(\w+)@mx';

    public const ENUM_REGEX = '@^enum\s+class\s+(\w+)@mx';

    //////////////////////////////////////////////////////////////

    public const INSTANCE_OF_REGEX = '@\s+instanceof\s+(\w+)@mx';
    public const CATCH_OF_REGEX = '@\}(?:\s+|)catch(?:\s+|)\((?:\s+|)(\w+)\s+@mx';

    /**
     * https://regex101.com/r/1y07xc/1
     */
    public const EXTENDS_REGEX = '@^(?:abstract\s+class|class)\s+\w+\s+extends\s+(\w+)\s+@mx';
    public const EXTENDS_WITH_BRACE_REGEX = '@^(?:abstract\s+class|class)\s+\w+\s+extends\s+(\w+)\{@mx';

    public const EXTENDS_REGEX4REPLACE = '@^(?:abstract\s+class|class)\s+\w+\s+extends\s+({{CLASS}})\s+@mx';
    public const EXTENDS_WITH_BRACE_REGEX4REPLACE = '@^(?:abstract\s+class|class)\s+\w+\s+extends\s+({{CLASS}})\{@mx';

    /**
     * https://regex101.com/r/U5zs4m/1
     */
    public const IMPLEMENTS_REGEX = '@\s+.*\s+implements\s+(.*)\s+\{@mx';
    public const TRAIT_REGEX = '@^trait\s+(\w+)@mx';
    public const NAMESPACE_REGEX = '@^namespace\s+(.*);$@mx';

    /**
     * https://regex101.com/r/iz1as2/2
     */
    public const NEW_REGEX = '@new\s+(\w+)@mx';
    public const NEW_REGEX_WITH_SLASH = '@new\s+\\\(\w+)@mx';

    public const NEW_REGEX4REPLACE = '@new\s+{{CLASS}}@mx';

    public const INSTANCEOF_REGEX4REPLACE = '@instanceof\s+{{CLASS}}@mx';
    public const CATCH_REGEX4REPLACE = '@\}(?:\s+|)catch(?:\s+|)\((?:\s+|){{CLASS}}\s+@mx';

    public const EXCEPTION_IN_CATCH_REGEX = '@catch\s+\((Exception)\s+@mixs';

    /**
     * https://regex101.com/r/9Okfgv/2
     */
    public const COLON_REGEX = '@(\w+)::@mx';
    public const COLON_REGEX_WITH_SLASH = '@\\\(\w+)::@mx';
    public const COLON_REGEX4REPLACE = '@{{CLASS}}::@mx';

    /**
     * https://regex101.com/r/kn3wPo/1
     */
    public const FUNCTION_REGEX = '@function\s+\w+\((.*)\)@m';

    /**
     * https://regex101.com/r/NtBPje/2
     */
    public const CLASS_NAME_REGEX = '@^(\w+)@mx';
    public const CLASS_NAME_REGEX4REPLACE = '@{{CLASS}}@mx';

    /**
     * https://regex101.com/r/vczAIe/1
     */
    public const CLASS_IN_TYPE_DEFINITION_REGEX = '@function\s+\w+(?:\s+|)\(.*\)(?:\s+|)\:(\w+)(?:\s+|)@mx';
    //////////////////////////////////////////////////////////////

    public const USE_REGEX = '/^use\s+(.*)\\{{CLASS}}/m';

    public const COMMENTS_REGEX = '~(?:#|//)[^\r\n]*|/\*.*?\*/~s';
    public const CONTENT_REGEX = '@\'.*?\'|\".*?\"@m';

    public const INCLUDES_GENERAL_REGEX = '@^(?:include_once|include|require|require_once)(?:\s+|)(?:\(|\s+|)(?:\'|").*(?:\'|")(?:\)|\s+|)(?:\s+|);@mx';
    public const INCLUDES_GENERAL_CAPTURE_REGEX = '@^(?:include_once|include|require|require_once)(?:\s+|)(?:\(|\s+|)(?:\'|")(.*)(?:\'|")(?:\)|\s+|)(?:\s+|);@mx';
    public const INCLUDES_GENERAL_CAPTURE_REPLACE_REGEX = '@^(?:include_once|include|require|require_once)(?:\s+|)(?:\(|\s+|)(?:\'|"){{SCRIPT}}(?:\'|")(?:\)|\s+|)(?:\s+|);@mx';
}