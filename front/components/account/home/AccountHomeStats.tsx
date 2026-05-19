import type { AccountHomeStat } from "./helpers";

type AccountHomeStatsProps = {
  stats: AccountHomeStat[];
};

export function AccountHomeStats({ stats }: AccountHomeStatsProps) {
  return (
    <section className="ac-home-grid">
      {stats.map((stat) => (
        <article className="ac-home-stat" key={stat.label}>
          <p className="ac-home-stat__label">{stat.label}</p>
          <p className="ac-home-stat__value">{stat.value}</p>
          <p className="ac-home-stat__hint">{stat.hint}</p>
        </article>
      ))}
    </section>
  );
}
