import { extend } from "flarum/common/extend";
import icon from "flarum/common/helpers/icon";
import Discussion from "flarum/common/models/Discussion";
import abbreviateNumber from "flarum/common/utils/abbreviateNumber";
import DiscussionListItem from "flarum/forum/components/DiscussionListItem";

export default function addLikesCount() {
  // Expose likeCount on Discussion (API already sends likeCount from DiscussionSerializer).
  if (!Discussion.prototype.likeCount) {
    Discussion.prototype.likeCount = function () {
      return this.attribute("likeCount") ?? 0;
    };
  }

  extend(DiscussionListItem.prototype, "infoItems", function (items) {
    const discussion = this.attrs.discussion;
    const count = discussion.likeCount();

    if (count > 0) {
      items.add(
        "likes",
        <span className="DiscussionListItem-likes">
          {icon("fas fa-heart", { className: "DiscussionListItem-likesIcon" })}
          <span className="DiscussionListItem-likesCount">
            {abbreviateNumber(count)}
          </span>
        </span>,
        5,
      );
    }
  });
}
