<?php

/**
 * @license
 *
 * sabre/katana.
 * Copyright (C) 2015 fruux GmbH (https://fruux.com/)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Sabre\Katana\Test\Unit;

use Sabre\Katana\Protocol as CUT;

/**
 * Test suite of the katana:// protocol.
 *
 * @copyright Copyright (C) 2015 fruux GmbH (https://fruux.com/).
 * @author Ivan Enderlin
 * @license GNU Affero General Public License, Version 3.
 *
 * @tags protocol
 */
class Protocol extends Suite
{
    public function case_application_public()
    {
        $this
            ->given($path = 'katana://public/')
            ->when($result = CUT::realPath($path))
            ->then
                ->string($result)
                    ->isEqualTo(
                        realpath(
                            __DIR__ . DS .
                            '..' . DS .
                            '..' . DS .
                            'public'
                        ) . DS
                    );
    }

    public function case_application_views()
    {
        $this
            ->given($path = 'katana://views/')
            ->when($result = CUT::realPath($path))
            ->then
                ->string($result)
                    ->isEqualTo(
                        realpath(
                            __DIR__ . DS .
                            '..' . DS .
                            '..' . DS .
                            'views'
                        ) . DS
                    );
    }

    public function case_data_root()
    {
        $this
            ->given($path = 'katana://data/')
            ->when($result = CUT::realPath($path))
            ->then
                ->string($result)
                    ->isEqualTo(
                        realpath(
                            __DIR__ . DS .
                            '..' . DS .
                            '..' . DS .
                            'data'
                        ) . DS
                    );
    }
}
