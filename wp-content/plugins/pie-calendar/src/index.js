import { registerPlugin } from "@wordpress/plugins";

import Piecal_Gutenberg_Sidebar_Plugin from "./piecal-postmeta-fields";

registerPlugin("piecal-postmeta-plugin", {
  render() {
    return <Piecal_Gutenberg_Sidebar_Plugin />;
  },
});
