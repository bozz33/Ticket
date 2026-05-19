import type { PublicContent } from "@/lib/types";
import { buildPublicUrl } from "@/lib/utils";

export function ShareLinks({ item }: { item: PublicContent }) {
  const publicUrl = encodeURIComponent(buildPublicUrl(`/${item.module}/${item.slug}`));
  const encodedTitle = encodeURIComponent(item.title);

  return (
    <div className="share-links">
      <span>Partager</span>
      <a href={`https://wa.me/?text=${encodedTitle}%20${publicUrl}`} rel="noreferrer" target="_blank">
        WhatsApp
      </a>
      <a href={`https://www.facebook.com/sharer/sharer.php?u=${publicUrl}`} rel="noreferrer" target="_blank">
        Facebook
      </a>
      <a href={`https://www.linkedin.com/sharing/share-offsite/?url=${publicUrl}`} rel="noreferrer" target="_blank">
        LinkedIn
      </a>
      <a href={`https://t.me/share/url?url=${publicUrl}&text=${encodedTitle}`} rel="noreferrer" target="_blank">
        Telegram
      </a>
    </div>
  );
}
