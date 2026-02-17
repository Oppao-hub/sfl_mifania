import { startStimulusApp } from '@symfony/stimulus-bridge';

// This starts the Stimulus app for any other built-in Symfony features
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));
