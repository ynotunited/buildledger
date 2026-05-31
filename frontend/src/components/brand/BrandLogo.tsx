import Link from "next/link";
import Image from "next/image";
import { cn } from "@/lib/utils";

type BrandLogoProps = {
  variant?: "color" | "white";
  href?: string;
  className?: string;
  priority?: boolean;
  alt?: string;
};

const LOGO_SOURCES = {
  color: "/brand/buildledger-web.png",
  white: "/brand/buildledger-white-web.png",
} as const;

export default function BrandLogo({
  variant = "color",
  href,
  className,
  priority = false,
  alt = "BuildLedger",
}: BrandLogoProps) {
  const image = (
    <Image
      src={LOGO_SOURCES[variant]}
      alt={alt}
      width={280}
      height={72}
      priority={priority}
      className={cn("h-auto w-auto", className)}
      sizes="(max-width: 768px) 180px, 280px"
    />
  );

  if (href) {
    return (
      <Link href={href} aria-label={alt} className="inline-flex items-center">
        {image}
      </Link>
    );
  }

  return image;
}
