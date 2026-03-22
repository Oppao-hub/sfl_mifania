<?php

namespace App\DataFixtures;

use App\Entity\Story;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StoryFixtures extends Fixture
{
    public const STORY_EVERYDAY = 'story_everyday';
    public const STORY_MINIMALIST = 'story_minimalist';
    public const STORY_MANIFESTO = 'story_manifesto';
    public const STORY_BAMBOO = 'story_bamboo';
    public const STORY_COTTON = 'story_cotton';
    public const STORY_HEMP = 'story_hemp';

    public function load(ObjectManager $manager): void
    {
        $stories = [
            self::STORY_EVERYDAY => [
                'title' => 'The Everyday Essential',
                'materialContent' => 'Crafted from 100% recycled post-consumer PET bottles, spun into a lightweight, ultra-durable ripstop fabric that expands to carry it all.',
                'artisanContent' => 'Cut and stitched by our partner cooperative, ensuring fair wages and safe working conditions for skilled local makers.',
                'dyeingContent' => 'Finished using a low-impact, closed-loop water system that uses 40% less water and prevents harmful chemicals from entering our waterways.'
            ],
            self::STORY_MINIMALIST => [
                'title' => 'The Urban Minimalist',
                'materialContent' => 'Engineered with water-resistant recycled nylon, stripped of unnecessary bulk to focus on clean lines and maximum utility.',
                'artisanContent' => 'Hand-finished edges and reinforced shoulder seams are meticulously inspected by our lead craftsmen to ensure decade-long durability.',
                'dyeingContent' => 'Colored using botanical and earth-safe synthetic dyes, achieving deep, neutral tones without compromising environmental integrity.'
            ],
            self::STORY_MANIFESTO => [
                'title' => 'The Mifania Manifesto',
                'materialContent' => 'We repurpose landfill-bound plastic waste into high-quality, resilient threads, actively closing the loop on global fashion waste.',
                'artisanContent' => 'Every seam is a testament to ethical labor. We partner directly with family-run workshops, bypassing sweatshops to support true craftsmanship.',
                'dyeingContent' => 'Our vibrant palettes reflect the natural world we fight to protect, achieved through zero-waste dyeing techniques that leave zero toxic footprint.'
            ],
            self::STORY_BAMBOO => [
                'title' => 'The Bamboo Utility',
                'materialContent' => 'Woven from 100% organic bamboo fibers, offering a naturally antimicrobial, biodegradable, and incredibly strong alternative to traditional canvas.',
                'artisanContent' => 'Hand-loomed by our partner artisans in rural communities, preserving traditional weaving techniques while providing sustainable, fair-wage livelihoods.',
                'dyeingContent' => 'Kept in its beautiful, unbleached natural beige state, requiring zero synthetic dyes and significantly reducing water consumption during production.'
            ],
            self::STORY_COTTON => [
                'title' => 'The Breezy Cotton',
                'materialContent' => 'Spun from GOTS-certified organic cotton, grown entirely without harmful pesticides to ensure a breathable, ultra-soft drape against your skin.',
                'artisanContent' => 'Cut and sewn by a female-led tailoring cooperative, ensuring fair trade wages and actively empowering women in the textile industry.',
                'dyeingContent' => 'Colored using an eco-friendly, low-impact dye process that safely recycles water and prevents toxic runoff from entering local ecosystems.'
            ],
            self::STORY_HEMP => [
                'title' => 'The Heritage Hemp',
                'materialContent' => 'Crafted from a luxurious blend of sustainable hemp and linen. Hemp requires a fraction of the water cotton does and actively absorbs CO2 as it grows.',
                'artisanContent' => 'Meticulously tailored with reinforced seams for longevity, designed to be a timeless wardrobe staple that completely transcends fast-fashion trends.',
                'dyeingContent' => 'Utilizes a gentle, botanical-based dyeing process to achieve its soft hue, completely free from heavy metals and harsh chemical fixatives.'
            ],
        ];

        foreach ($stories as $reference => $data) {
            $story = new Story();
            $story->setTitle($data['title']);
            $story->setMaterialContent($data['materialContent']);
            $story->setArtisanContent($data['artisanContent']);
            $story->setDyeingContent($data['dyeingContent']);

            $manager->persist($story);

            // Add a reference so other fixtures (like ProductFixtures) can grab this exact story
            $this->addReference($reference, $story);
        }

        $manager->flush();
    }
}
