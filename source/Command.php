<?php

namespace Cli;

/**
 * Abstract command class
 * @author alxmsl
 * @date 10/22/12
 */
abstract class Command {
    /**
     * @var string run script name
     */
    protected $script = '';

    /**
     * @var string run command name
     */
    protected $command = '';

    /**
     * @var array command parameters
     */
    protected $parameters = array();

    /**
     * @var array parameters events
     */
    protected $events = array();

    public function __construct() {
        switch (true) {
            case PHP_SAPI != 'cli':
                throw new IncorrectSAPIException();
            case !array_key_exists('_', $_SERVER) || is_null($_SERVER['_']):
                throw new IncorrectEnvironmentException();
        }
        $this->script = $_SERVER['_'];
        $this->command = $_SERVER['argv'][0];
    }

    /**
     * Append parameter method
     * @param Parameter $Parameter adding parameter instance
     * @param null|\Closure $Handler closure function if parameter will be define. If NULL - event will not activated
     * @throws ParameterEventTypeException exception if $Closure have not \Closure type
     * @throws UnsupportedParameterTypeException if $Parameter instance has unsupported type
     */
    public function appendParameter(Parameter $Parameter, $Handler = null) {
        /** @var $Parameter Option */
        switch (true) {
            case $Parameter instanceof Option:
                $this->parameters[$Parameter->getLong()] = $Parameter;
                if (!is_null($Handler)) {
                    if ($Handler instanceof \Closure) {
                        $this->setEvent($Parameter, $Handler);
                    } else {
                        throw new ParameterEventTypeException();
                    }
                }
                $this->clearCache();
                break;
            default:
                throw new UnsupportedParameterTypeException();
        }
    }

    public function appendHelpParameter($description) {
        $Option = new Option('help', 'h', $description);
        $Self = $this;
        $this->appendParameter($Option, function() use ($Self) {
            $Self->displayHelp();
        });
    }

    /**
     * Set event for added parameter
     * @param Parameter $Parameter adding parameter instance
     * @param \Closure $Handler closure function if parameter will be define
     * @throws ParameterNotFoundException if $Parameter instance is not added to command
     * @throws UnsupportedParameterTypeException if $Parameter instance has unsupported type
     */
    public function setEvent(Parameter $Parameter, \Closure $Handler) {
        /** @var $Parameter Option */
        switch (true) {
            case $Parameter instanceof Option:
                $long = $Parameter->getLong();
                if (isset($this->parameters[$long])) {
                    $this->events[$long] = $Handler;
                } else {
                    throw new ParameterNotFoundException();
                }
                break;
            default:
                throw new UnsupportedParameterTypeException();
        }
    }

    /**
     * Abstract parse command line method
     */
    abstract public function parse();

    /**
     * Abstract cache support function
     */
    abstract protected function clearCache();

    /**
     * Display command help
     */
    abstract public function displayHelp();
}

/**
 * Base Cli exception. All exception extends this
 */
class CliLogicException extends \Exception {}
final class IncorrectSAPIException extends CliLogicException {}
final class IncorrectEnvironmentException extends CliLogicException {}
final class UnsupportedParameterTypeException extends CliLogicException {}
final class IncorrectParameterValueTypeException extends CliLogicException {}
final class ParameterNotFoundException extends CliLogicException {}
final class ParameterEventTypeException extends CliLogicException {}

class CliCallException extends CliLogicException {}
final class DuplicateOptionException extends CliCallException {}
final class RequiredOptionException extends CliCallException {}

