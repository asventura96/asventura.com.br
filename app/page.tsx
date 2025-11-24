import Image from "next/image";
import HeroSection from '@/components/HeroSection';
import { getProjects, Project } from '@/utils/api';
import ProjectsSection from '@/components/ProjectsSection';
import ContactForm from '@/components/ContactForm';

export default async function Home() {
  const projects = await getProjects();
  return (
    <div className="min-h-screen bg-zinc-50 font-sans dark:bg-black">
      <main className="w-full">
        <HeroSection />
        <ProjectsSection projects={projects} />
        <ContactForm />
      </main>
    </div>
  );
}
