<?php
use PHPUnit\Framework\TestCase;
use Asper\SlackBot\Currency;
use Asper\Service\SlackBot;

class CurrencyTest extends TestCase
{
    public $slackBot;

    public function createFakeSlackBot(string $text)
    {
        $commands = explode(" ", $text);
        $trigger_word = array_shift($commands);

        $token = 'fakeToken';
        $_POST['trigger_word'] = $trigger_word;
        $_POST['text'] = $text;
        $_POST['token'] = $token;

        return new SlackBot($token, 'fakeUrl');
    }

    public function mockSlackBot(string $text)
    {
        $commands = explode(" ", $text);
        $trigger_word = array_shift($commands);

        $token = 'fakeToken';
        $_POST['trigger_word'] = $trigger_word;
        $_POST['text'] = $text;
        $_POST['token'] = $token;

        $stub = $this->getMockBuilder(SlackBot::class)
                        ->setConstructorArgs([$token, 'fakeHookUrl'])
                        ->setMethods(['sendMsgforGAE'])
                        ->getMock();

        $stub->expects($this->any())
            ->method('sendMsgforGAE')
            ->will($this->returnArgument(0));

        return $stub;
    }


    /** @test */
    public function it_can_calculate_target_currency_to_local_currency()
    {
        $slackBot = $this->mockSlackBot('匯率 10000 JPY');

        $currencyBot = new Currency;

        $response = $currencyBot->trigger($slackBot);

        $this->assertContains('JPY', $response);
        $this->assertContains(number_format(10000), $response);
    }
}
