import manifest from '@neos-project/neos-ui-extensibility';
import AnchorView from './AnchorView';

manifest('CodeQ.JumpMarkers:AnchorView', {}, globalRegistry => {
    const viewsRegistry = globalRegistry.get('inspector').get('views');

    viewsRegistry.set('CodeQ.JumpMarkers/Views/AnchorView', {
        component: AnchorView,
        hasOwnLabel: true
    });
});
