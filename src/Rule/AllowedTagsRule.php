<?php

namespace DTL\GherkinLint\Rule;

use Cucumber\Messages\FeatureChild;
use Cucumber\Messages\GherkinDocument;
use Cucumber\Messages\Scenario;
use Cucumber\Messages\Tag;
use DTL\GherkinLint\Model\FeatureDiagnostic;
use DTL\GherkinLint\Model\FeatureDiagnosticSeverity;
use DTL\GherkinLint\Model\Range;
use DTL\GherkinLint\Model\Rule;
use DTL\GherkinLint\Model\RuleConfig;
use DTL\GherkinLint\Model\RuleDescription;
use Generator;

class AllowedTagsRule implements Rule
{
    public function analyse(GherkinDocument $document, RuleConfig $config): Generator
    {
        assert($config instanceof AllowedTagsConfig);

        if (null === $config->allow) {
            return;
        }

        yield from $this->checkTags(
            $document->feature?->tags,
            $config->allow
        );

        foreach ($document->feature->children ?? [] as $child) {
            if (!$child instanceof FeatureChild) {
                continue;
            }
            if (!$child->scenario instanceof Scenario) {
                continue;
            }

            yield from $this->checkTags($child->scenario->tags, $config->allow);
        }
    }

    public function describe(): RuleDescription
    {
        return new RuleDescription(
            'allowedTags',
            'Only permit specified tags',
            AllowedTagsConfig::class,
        );
    }

    /**
     * @return Generator<FeatureDiagnostic>
     * @param ?list<Tag> $tags
     * @param string[] $allowedTags
     */
    private function checkTags(?array $tags, array $allowedTags): Generator
    {
        if (null === $tags) {
            return;
        }

        foreach ($tags as $tag) {
            if (in_array($tag->name, $allowedTags)) {
                continue;
            }

            yield new FeatureDiagnostic(
                Range::fromLocationAndName($tag->location, $tag->name),
                FeatureDiagnosticSeverity::WARNING,
                sprintf('Tag "%s" is not allowed', $tag->name)
            );
        }
    }
}