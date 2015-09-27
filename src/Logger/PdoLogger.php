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
* @category  Logger
* @package   Pris\ChannelLog
* @author    Jake Johns <jake@jakejohns.net>
* @copyright 2015 Jake Johns
* @license   http://www.gnu.org/licenses/agpl-3.0.txt AGPL V3
* @link      http://github.com/prisbot/pris.channellog
 */

namespace Pris\ChannelLog\Logger;

use Phergie\Irc\Event\EventInterface;
use PDO;
use DateTime;

/**
 * Plugin
 *
 * @category Logger
 * @package  Pris\ChannelLog
 * @author   Jake Johns <jake@jakejohns.net>
 * @license  http://www.gnu.org/licenses/agpl-3.0.txt AGPL V3
 * @link     http://github.com/prisbot/pris.channellog
 *
 */
class PdoLogger implements LoggerInterface
{

    /**
     * stmt
     *
     * @var mixed
     * @access protected
     */
    protected $stmt;

    /**
     * __construct
     *
     * @param PDO    $pdo   pdo
     * @param string $table table name
     *
     * @access public
     */
    public function __construct(PDO $pdo, $table = 'logs')
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->stmt = $pdo->prepare(
            "INSERT INTO {$table}"
            . '(received, nick, source, event, message) '
            . 'VALUES(:date, :nick, :src, :evt, :msg)'
        );
    }

    /**
     * log
     *
     * @param Event $event event to log
     *
     * @return void
     *
     * @access public
     */
    public function log(EventInterface $event)
    {
        $params = $event->getParams();

        $data = [
            ':date' => (new DateTime)->format(DateTime::ISO8601),
            ':nick' => $event->getNick(),
            ':src'  => $event->getSource(),
            ':evt'  => $event->getCommand(),
            ':msg'  => isset($params['text']) ? $params['text'] : null
        ];

        $this->stmt->execute($data);
    }
}
