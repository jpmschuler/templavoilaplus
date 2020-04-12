<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Renderer;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/** @TODO Missing Base class */
use Ppi\TemplaVoilaPlus\Domain\Model\TemplateYamlConfiguration;

interface RendererInterface
{

    public function renderTemplate(TemplateYamlConfiguration $templateConfiguration, array $processedValues, array $row): string;
}