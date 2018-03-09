<?php

namespace Nuwave\Lighthouse\Schema\Directives\Fields;

use GraphQL\Language\AST\DirectiveNode;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Exceptions\DirectiveException;
use Nuwave\Lighthouse\Support\Traits\HandlesDirectives;

class FieldDirective implements FieldResolver
{
    use HandlesDirectives;

    /**
     * Name of the directive.
     *
     * @return string
     */
    public function name()
    {
        return 'field';
    }

    /**
     * Resolve the field directive.
     *
     * @param FieldValue $value
     *
     * @return FieldValue
     */
    public function handle(FieldValue $value)
    {
        $directive = $this->fieldDirective($value->getField(), $this->name());
        $className = $this->getClassName($directive);
        $method = $this->getMethod($directive);
        $data = $this->argValue(collect($directive->arguments)->first(function ($arg) {
            return 'args' === data_get($arg, 'name.value');
        }));

        return $value->setResolver(function ($root, array $args, $context = null, $info = null) use ($className, $method, $data) {
            $instance = app($className);

            return call_user_func_array(
                [$instance, $method],
                [$root, array_merge($args, ['directive' => $data]), $context, $info]
            );
        });
    }

    /**
     * Get class name for resolver.
     *
     * @param DirectiveNode $directive
     *
     * @return string
     */
    protected function getClassName(DirectiveNode $directive)
    {
        $class = $this->directiveArgValue($directive, 'class');

        if (! $class) {
            throw new DirectiveException(sprintf(
                'Directive [%s] must have a `class` argument.',
                $directive->name->value
            ));
        }

        return $class;
    }

    /**
     * Get method for resolver.
     *
     * @param DirectiveNode $directive
     *
     * @return string
     */
    protected function getMethod(DirectiveNode $directive)
    {
        $method = $this->directiveArgValue($directive, 'method');

        if (! $method) {
            throw new DirectiveException(sprintf(
                'Directive [%s] must have a `method` argument.',
                $directive->name->value
            ));
        }

        return $method;
    }
}
