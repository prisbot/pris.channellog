<?php
/**
* Pris\ChannelLog
*
* PHP version 5
*
* This program is free software: you can redistribute it and/or modify it
* under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or (at your
* option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @category  Plugin
* @package   Pris\ChannelLog
* @author    Jake Johns <jake@jakejohns.net>
* @copyright 2015 Jake Johns
* @license   http://www.gnu.org/licenses/agpl-3.0.txt AGPL V3
* @link      http://github.com/prisbot/pris.channellog
 */

namespace Pris\ChannelLog;

use Pris\ChannelLog\Logger\LoggerInterface;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as Event;

use Phergie\Irc\Event\EventInterface;

use DateTime;

/**
 * Plugin
 *
 * @category Plugin
 * @package  Pris\ChannelLog
 * @author   Jake Johns <jake@jakejohns.net>
 * @license  http://www.gnu.org/licenses/agpl-3.0.txt AGPL V3
 * @link     http://github.com/prisbot/pris.channellog
 *
 */
class Plugin extends AbstractPlugin
{
    /**
     * default logging state
     *
     * @var bool
     * @access protected
     */
    protected $default = true;

    /**
     * channel settings
     *
     * @var array
     * @access protected
     */
    protected $channels = [];

    /**
     * should log private messages to the bot?
     *
     * @var bool
     * @access protected
     */
    protected $logpm = false;

    /**
     * events
     *
     * @var array
     * @access protected
     */
    protected $events = [
        'irc.received.privmsg',
        'irc.received.notice',
        'irc.received.join',
        'irc.received.part',
    ];

    /**
     * channelLog
     *
     * @var LoggerInterface
     * @access protected
     */
    protected $channelLog;

    /**
     * __construct
     *
     * @param array $config config options
     *
     * @access public
     */
    public function __construct(array $config = [])
    {
        $keys = ['default', 'events', 'channels', 'logpm'];
        foreach ($keys as $key) {
            if (isset($config[$key])) {
                $this->$key = $config[$key];
            }
        }

        $this->setChannelLog($config['channellog']);
    }

    /**
     * setChannelLog
     *
     * @param ChannelLogInterface $logger logger
     *
     * @return Plugin
     *
     * @access public
     */
    public function setChannelLog(LoggerInterface $logger)
    {
        $this->channellog = $logger;
        return $this;
    }


    /**
     * getSubscribedEvents
     *
     * @return array
     *
     * @access public
     */
    public function getSubscribedEvents()
    {
        $events['command.log'] = 'status';

        foreach ($this->events as $event) {
            $events[$event] = 'log';
        }

        return $events;
    }

    /**
     * status
     *
     * @param Event $event event
     * @param Queue $queue queue
     *
     * @return void
     *
     * @access public
     */
    public function status(Event $event, Queue $queue)
    {
        $source = $event->getSource();
        $params = $event->getCustomParams();

        if (! $params) {
            $queue->ircPrivmsg(
                $source,
                $this->isLogging($source)
                ? 'Logging is ENABLED'
                : 'Logging is DISABLED'
            );
            return;
        }

        $this->setLogging($source, $params[0], $queue);
    }

    /**
     * setLogging
     *
     * @param string $source source
     * @param string $value  value
     * @param Queue  $queue  queue
     *
     * @return void
     *
     * @access protected
     */
    protected function setLogging($source, $value, Queue $queue)
    {
        $enable = ['on', 'true', '1', 'yes'];
        $disable = ['off', 'false', '0', 'no'];

        $value = trim(strtolower($value));

        if (in_array($value, $enable)) {
            $this->channels[$source] = true;
            $queue->ircPrivmsg($source, 'Logging enabled');
            return;
        }

        if (in_array($value, $disable)) {
            $this->channels[$source] = false;
            $queue->ircPrivmsg($source, 'Logging disabled');
            return;
        }

        $queue->ircPrivmsg($source, 'Invalid logging value');
    }

    /**
     * isLogging
     *
     * @param string $source name of source
     *
     * @return bool
     *
     * @access protected
     */
    protected function isLogging($source)
    {
        if (isset($this->channels[$source])) {
            return $this->channels[$source];
        }

        if (substr($source, 0, 1) == '#') {
            return $this->default;
        }

        return $this->logpm;
    }

    /**
     * log
     *
     * @param Event $event event
     * @param Queue $queue queue
     *
     * @return void
     *
     * @access public
     */
    public function log(EventInterface $event, Queue $queue)
    {
        if ($this->isLogging($event->getSource())) {
            try {
                $this->channellog->log($event);
            } catch (Exception $e) {
                $queue->ircPrivmsg(
                    $event->getSource(),
                    'Error logging message'
                );
            }
        }
    }
}
