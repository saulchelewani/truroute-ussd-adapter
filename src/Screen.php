<?php


namespace TNM\USSD;


use TNM\USSD\Http\Request;
use TNM\USSD\Http\Response;

abstract class Screen
{
    /**
     * USSD Request object
     *
     * @var Request
     */
    public $request;

    /**
     * Screen constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    /**
     * Create an instance of a screen
     *
     * @param Request $request
     * @return Screen
     */
    private static function getInstance(Request $request): Screen
    {
        return new $request->trail->{'state'}($request);
    }

    /**
     * Add message to the screen
     *
     * @return string
     */
    abstract protected function message(): string;

    /**
     * Add options to the screen
     * @return array
     */
    abstract protected function options(): array;

    /**
     * Execute the selected option/action
     *
     * @return mixed
     */
    abstract protected function execute();

    /**
     * Retrieve payload passed to the session
     * @return string
     */
    protected function payload(): string
    {
        return $this->request->trail->{'payload'};
    }

    /**
     * Response type: Release or Response
     *
     * @return int
     */
    protected function type(): int
    {
        return Response::RESPONSE;
    }

    /**
     * Get value equivalent to the selected option
     *
     * @param int $value
     * @return string
     */
    public function getItemAt(int $value): string
    {
        return $this->options()[$value - 1];
    }

    /**
     * Prepare the options as output string
     *
     * @return string
     */
    protected function optionsAsString(): string
    {
        $string = '';
        for ($i = 0; $i < count($this->options()); $i++) {
            $string .= sprintf("%s. %s\n", $i + 1, $this->options()[$i]);
        }
        return $string;
    }

    /**
     * Render the USSD response
     *
     * @return string
     */
    public function render(): string
    {
        $this->request->trail->update(['state' => static::class]);
        return response()->ussd(
            sprintf("%s\n%s %s",
                $this->message(),
                $this->optionsAsString(),
                $this->goesBack() ? "\n0. Home \n#. Back": ""
            ), $this->type()
        );
    }

    /**
     * Handle USSD request
     *
     * @param Request $request
     */
    public static function handle(Request $request)
    {
        $screen = static::getInstance($request);
        $screen->execute();
    }

    /**
     * Retrieve the value passed with the USSD response
     *
     * @return string
     */
    public function getRequestValue(): string
    {
        if (count($this->options())) return $this->getItemAt($this->request->message);
        return $this->request->message;
    }

    protected function goesBack(): bool
    {
        return true;
    }
}