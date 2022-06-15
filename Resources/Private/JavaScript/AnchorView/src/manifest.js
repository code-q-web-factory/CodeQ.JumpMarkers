import manifest from '@neos-project/neos-ui-extensibility';
import AnchorView from './AnchorView';
import SectionIdEditor from "./SectionIdEditor";

manifest('CodeQ.JumpMarkers:AnchorView', {}, globalRegistry => {
	const viewsRegistry = globalRegistry.get('inspector').get('views');
	const editorsRegistry = globalRegistry.get('inspector').get('editors');

	viewsRegistry.set('CodeQ.JumpMarkers/Views/AnchorView', {
		component: AnchorView,
		hasOwnLabel: true
	});

	editorsRegistry.set('CodeQ.JumpMarkers/Inspector/Editors/SegmentIdEditor', {
		component: SectionIdEditor
	});
});
